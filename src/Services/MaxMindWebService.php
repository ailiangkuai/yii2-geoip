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
    private $locales = ['cn'];

    /**
     * MAXMIND USER ID
     * @var string
     */
    private $user_id = null;

    /**
     * MAXMIND LICENSE KEY
     * @var string
     */
    private $license_key = null;


    public function setLocales($locales)
    {
        $this->locales = $locales;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setLicenseKey($license_key)
    {
        $this->license_key = $license_key;
    }

    /**
     * The "booting" method of the service.
     *
     * @return void
     */
    public function boot()
    {
        $this->client = new Client(
            $this->user_id,
            $this->license_key,
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
            'iso_code'    => $record->country->isoCode,
            'country'     => $record->country->name,
            'province'    => ArrayHelper::getValue($record, 'subdivisions.0.name', '') ?: '',
            'city'        => $record->city->name,
            'state'       => $record->mostSpecificSubdivision->isoCode,
            'state_name'  => $record->mostSpecificSubdivision->name,
            'postal_code' => $record->postal->code,
            'lat'         => $record->location->latitude,
            'lon'         => $record->location->longitude,
            'timezone'    => $record->location->timeZone,
            'continent'   => $record->continent->code,
        ]);
    }
}