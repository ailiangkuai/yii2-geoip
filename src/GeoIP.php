<?php

namespace ailiangkuai\yii2\GeoIP;

use ailiangkuai\yii2\GeoIP\Services\ServiceInterface;
use Exception;
use yii\base\InvalidParamException;
use yii\base\Object;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\redis\Cache;

class GeoIP extends Object
{

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log settings for when a location is not found
    | for the IP provided.
    |
    */
    protected $log_failures = true;

    /*
    |--------------------------------------------------------------------------
    | Include Currency in Results
    |--------------------------------------------------------------------------
    |
    | When enabled the system will do it's best in deciding the user's currency
    | by matching their ISO code to a preset list of currencies.
    |
    */

    protected $include_currency = true;

    /**
     * Remote Machine IP address.
     *
     * @var float
     */
    protected $remote_ip = null;

    /**
     * Current location instance.
     *
     * @var Location
     */
    protected $location = null;

    /**
     * Currency data.
     *
     * @var array
     */
    protected $currencies = null;

    /**
     * GeoIP service id.
     *
     * @var string
     */
    protected $serviceId;

    /**
     * GeoIP service instance.
     *
     * @var ServiceInterface
     */
    private $service;

    /*
    |--------------------------------------------------------------------------
    | Storage Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many storage drivers as you wish.
    |
    */
    protected $services;

    /**
     * Cache manager instance.
     *
     * @var Cache
     */
    protected $cache = 'cache';

    /**
     * Default Location data.
     *
     * @var array
     */
    protected $default_location = [
        'ip'          => '127.0.0.0',
        'iso_code'    => 'Cn',
        'country'     => 'China',
        'city'        => 'Guangzhou',
        'state'       => 'Gz',
        'state_name'  => 'Guangdong',
        'postal_code' => '510000',
        'lat'         => 23.1167,
        'lon'         => 113.25,
        'timezone'    => 'Asia/Shanghai',
        'continent'   => 'NA',
        'default'     => true,
        'currency'    => 'CNY',
        'cached'      => false,
    ];

    /**
     *
     * @author yaoyongfeng
     */
    public function init()
    {
        parent::init();
        $this->cache && $this->cache = Instance::ensure($this->cache, \yii\redis\Cache::className());
        // Set IP
        $this->remote_ip = $this->default_location['ip'] = $this->getClientIP();
    }

    /**
     *
     * @param string $service
     * @return string
     * @author yaoyongfeng
     */
    public function setServiceId($serviceId)
    {
        return $this->serviceId = $serviceId;
    }

    public function setServices(array $services)
    {
        foreach ($services as $key => $service) {
            if (\Yii::createObject($service) instanceof ServiceInterface == false) {
                throw new InvalidParamException('Configuration driver error, please confirm class implements ailiangkuai\yii2\GeoIP\Services\ServiceInterface');
            }
        }
        $this->services = $services;
    }

    /**
     *
     * @param $cache
     * @author yaoyongfeng
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    public function setDefaultLocation($default_location)
    {
        // Set custom default location
        $this->default_location = array_merge(
            $this->default_location,
            $default_location
        );
    }

    /**
     * set Logging Configuration
     * @param $log_failures
     * @author yaoyongfeng
     */
    public function setLogFailures($log_failures)
    {
        $this->log_failures = (bool)$log_failures;
    }

    /**
     * set Include Currency in Results
     * @param $include_currency
     * @author yaoyongfeng
     */
    public function setIncludeCurrency($include_currency)
    {
        $this->include_currency = (bool)$include_currency;
    }


    /**
     * Get the location from the provided IP.
     *
     * @param string $ip
     *
     * @return \ailiangkuai\yii2\GeoIP\Location
     */
    public function getLocation($ip = null)
    {
        // Get location data
        $this->location = $this->find($ip);

        // Should cache location
        if ($this->shouldCache($ip, $this->location)) {
            $this->getCache()->set($ip, $this->location);
        }

        return $this->location;
    }

    /**
     * Find location from IP.
     *
     * @param string $ip
     *
     * @return \ailiangkuai\yii2\GeoIP\Location
     * @throws \Exception
     */
    private function find($ip = null)
    {
        // Check cache for location
        if ($this->getCache() && $location = $this->getCache()->get($ip)) {
            $location->cached = true;
            return $location;
        }

        // If IP not set, user remote IP
        $ip = $ip ?: $this->remote_ip;

        // Check if the ip is not local or empty
        if ($this->isValid($ip)) {
            try {
                // Find location
                $location = $this->getService()->locate($ip);

                // Set currency if not already set by the service
                if (!$location->currency) {
                    $location->currency = $this->getCurrency($location->iso_code);
                }

                // Set default
                $location->default = false;

                return $location;
            } catch (\Exception $e) {
                if ($this->log_failures === true) {
                    \Yii::error($e->getMessage(), 'geoip');
                }
            }
        }

        return $this->getService()->hydrate($this->default_location);
    }

    /**
     * Get the currency code from ISO.
     *
     * @param string $iso
     *
     * @return string
     */
    public function getCurrency($iso)
    {
        if ($this->currencies === null && $this->include_currency) {
            $this->currencies = include(__DIR__ . '/Support/Currencies.php');
        }

        return ArrayHelper::getValue($this->currencies, $iso);
    }

    /**
     * Get service instance.
     *
     * @return \ailiangkuai\yii2\GeoIP\Services\ServiceInterface
     * @throws Exception
     */
    public function getService()
    {
        if (!isset($this->services[$this->serviceId]) || empty($this->services[$this->serviceId])) {
            throw new InvalidParamException('The service id is not configured correctly');
        }
        if ($this->service === null) {
            $this->service = \Yii::createObject($this->services[$this->serviceId]);
        }

        return $this->service;
    }

    /**
     * Get cache instance.
     *
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get the client IP address.
     *
     * @return string
     */
    public function getClientIP()
    {
        $remotes_keys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
            'HTTP_X_CLUSTER_CLIENT_IP',
        ];

        foreach ($remotes_keys as $key) {
            if ($address = getenv($key)) {
                foreach (explode(',', $address) as $ip) {
                    if ($this->isValid($ip)) {
                        return $ip;
                    }
                }
            }
        }

        return '127.0.0.0';
    }

    /**
     * Checks if the ip is valid.
     *
     * @param string $ip
     *
     * @return bool
     */
    private function isValid($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the location should be cached.
     *
     * @param string $ip
     * @param Location $location
     *
     * @return bool
     */
    private function shouldCache($ip = null, Location $location)
    {
        //默认location或已经缓存过就不需要缓存
        if ($location->default === true || $location->cached === true) {
            return false;
        }
        if ($this->cache) {
            return true;
        }
//        switch ($this->config('cache', 'none')) {
//            case 'all':
//                return true;
//            case 'some' && $ip === null:
//                return true;
//        }

        return false;
    }


}
