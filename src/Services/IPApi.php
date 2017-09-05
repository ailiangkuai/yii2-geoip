<?php

namespace ailiangkuai\yii2\GeoIP\Services;

use Exception;
use ailiangkuai\yii2\GeoIP\Support\HttpClient;
use yii\helpers\ArrayHelper;

class IPApi extends AbstractService
{
    /**
     * Http client instance.
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * An array of continents.
     *
     * @var array
     */
    protected $continents;

    /** IPAPI_KEY
     * @var string
     */
    private $key = null;

    /** 是否通过https访问接口
     * @var bool
     */
    private $secure = true;

    /**
     * @var string
     */
    private $continent_path = null;

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function setSecure($secure)
    {
        $this->secure = $secure;
    }

    public function setContinentPath($continent_path)
    {
        $this->continent_path = $continent_path;
    }


    /**
     * The "booting" method of the service.
     *
     * @return void
     */
    public function boot()
    {
        $base = [
            'base_uri' => 'http://ip-api.com/',
            'headers'  => [
                'User-Agent' => 'YII2-GeoIP',
            ],
            'query'    => [
                'fields' => 49663,
            ],
        ];

        // Using the Pro service
        if ($this->key) {
            $base['base_uri'] = ($this->secure ? 'https' : 'http') . '://pro.ip-api.com/';
            $base['query']['key'] = $this->key;
        }

        $this->client = new HttpClient($base);

        // Set continents
        if (file_exists($this->continent_path)) {
            $this->continents = json_decode(file_get_contents($this->continent_path), true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function locate($ip)
    {
        // Get data from client
        $data = $this->client->get('json/' . $ip);

        // Verify server response
        if ($this->client->getErrors() !== null) {
            throw new Exception('Request failed (' . $this->client->getErrors() . ')');
        }

        // Parse body content
        $json = json_decode($data[0]);

        // Verify response status
        if ($json->status !== 'success') {
            throw new Exception('Request failed (' . $json->message . ')');
        }

        return $this->hydrate([
            'ip'          => $ip,
            'iso_code'    => $json->countryCode,
            'country'     => $json->country,
            'province'    => ArrayHelper::getValue($json, 'subdivisions.0.name', '') ?: '',
            'city'        => $json->city,
            'state'       => $json->region,
            'state_name'  => $json->regionName,
            'postal_code' => $json->zip,
            'lat'         => $json->lat,
            'lon'         => $json->lon,
            'timezone'    => $json->timezone,
            'continent'   => $this->getContinent($json->countryCode),
        ]);
    }

    /**
     * Update function for service.
     *
     * @return string
     * @throws Exception
     */
    public function update()
    {
        if ($this->continent_path === false) {
            throw new Exception('Continent path not set in config file.');
        }

        $data = $this->client->get('http://dev.maxmind.com/static/csv/codes/country_continent.csv');

        // Verify server response
        if ($this->client->getErrors() !== null) {
            throw new Exception($this->client->getErrors());
        }

        $lines = explode("\n", $data[0]);

        array_shift($lines);

        $output = [];

        foreach ($lines as $line) {
            $arr = str_getcsv($line);

            if (count($arr) < 2) {
                continue;
            }

            $output[$arr[0]] = $arr[1];
        }

        // Get path
        $path = $this->continent_path;

        file_put_contents($path, json_encode($output));

        return "Continent file ({$path}) updated.";
    }

    /**
     * Get continent based on country code.
     *
     * @param string $code
     *
     * @return string
     */
    private function getContinent($code)
    {
        return ArrayHelper::getValue($this->continents, $code, 'Unknown');
    }
}