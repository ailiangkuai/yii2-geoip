<?php

namespace ailiangkuai\yii2\GeoIP\Services;

use GeoIp2\WebService\Client;
use GeoIp2\Exception\AddressNotFoundException;
use yii\helpers\ArrayHelper;

class MaxMindWebService extends AbstractService
{
    /**
     * Service client instance.
     *
     * @var \GeoIp2\WebService\Client
     */
    protected $client;

    /**
     * locales language
     * @var array
     */
    private $locales = ['zh-CN'];

    /**
     * MAXMIND USER ID
     * @var string
     */
    private $userId = null;

    /**
     * MAXMIND LICENSE KEY
     * @var string
     */
    private $licenseKey = null;


    public function setLocales($locales)
    {
        $this->locales = $locales;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setLicenseKey($licenseKey)
    {
        $this->licenseKey = $licenseKey;
    }

    /**
     * The "booting" method of the service.
     *
     * @return void
     */
    public function boot()
    {
        $this->client = new Client(
            $this->userId,
            $this->licenseKey,
            $this->locales
        );
    }

    /**
     * {@inheritdoc}
     */
    public function locate($ip)
    {
        $record = $this->client->city($ip);

        return $this->hydrate([
            'ip'          => $ip,
            'iso_code'    => $record->country->isoCode ?: '',
            'country'     => $record->country->name ?: '',
            'city'        => $record->city->name ?: '',
            'state'       => $record->mostSpecificSubdivision->isoCode ?: '',
            'state_name'  => $record->mostSpecificSubdivision->name ?: '',
            'postal_code' => $record->postal->code ?: 0,
            'lat'         => $record->location->latitude ?: 0,
            'lon'         => $record->location->longitude ?: 0,
            'timezone'    => $record->location->timeZone ?: '',
            'continent'   => $record->continent->code ?: '',
        ]);
    }
}