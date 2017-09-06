<?php

namespace ailiangkuai\yii2\GeoIP\Services;

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class MaxMindDatabase extends AbstractService
{
    /**
     * Service reader instance.
     *
     * @var \GeoIp2\Database\Reader
     */
    protected $reader;

    private $database_path = false;
    private $locales = ['zh-CN'];
    private $update_url = 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';

    public function setDatabasePath($database_path)
    {
        if (!is_dir(dirname($database_path))) {
            FileHelper::createDirectory(dirname($database_path));
        }
        $this->database_path = $database_path;
    }

    public function setLocales($locales)
    {
        $this->locales = $locales;
    }

    public function setUpdateUrl($update_url)
    {
        $this->update_url = $update_url;
    }

    /**
     * The "booting" method of the service.
     *
     * @return void
     */
    public function boot()
    {
        // Copy test database for now
        if (file_exists($this->database_path) === false) {
            copy(__DIR__ . '/../../resources/geoip.mmdb', $this->database_path);
        }

        $this->reader = new Reader(
            $this->database_path,
            $this->locales
        );
    }

    /**
     * {@inheritdoc}
     */
    public function locate($ip)
    {
        $record = $this->reader->city($ip);

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

    /**
     * Update function for service.
     *
     * @return string
     * @throws Exception
     */
    public function update()
    {
        if ($this->database_path === false) {
            throw new Exception('Database path not set in config file.');
        }

        // Get settings
        $url = $this->update_url;
        $path = $this->database_path;

        // Get header response
        $headers = get_headers($url);

        if (substr($headers[0], 9, 3) != '200') {
            throw new Exception('Unable to download database. (' . substr($headers[0], 13) . ')');
        }

        // Download zipped database to a system temp file
        $tmpFile = tempnam(sys_get_temp_dir(), 'maxmind');
        file_put_contents($tmpFile, fopen($url, 'r'));

        // Unzip and save database
        file_put_contents($path, gzopen($tmpFile, 'r'));

        // Remove temp file
        @unlink($tmpFile);

        return "Database file ({$path}) updated.";
    }
}
