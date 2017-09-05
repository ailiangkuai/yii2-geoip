# GeoIp for yii2
使用方法
#install
## configuration
```php
return [
    'components' => [
       'geoIp'                    => [
                   'class'     => \ailiangkuai\yii2\GeoIP\GeoIP::className(),
                   'cache'     => [
                       'keyPrefix'       => 'yii2-geoip-location',
                       'defaultDuration' => 30,
                   ],
                   'serviceId' => 'maxmindDatabase',
                   'services'  => [
                       'maxmindDatabase' => [
                           'class'        => \ailiangkuai\yii2\GeoIP\Services\MaxMindDatabase::className(),
                           'databasePath' => \Yii::getAlias('@runtime/backend/geoip.mmdb'),
                           'updateUrl'    => 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz',
                           'locales'      => ['cn'],
                       ],
                       'maxmindApi'      => [
                           'class'       => \ailiangkuai\yii2\GeoIP\Services\MaxMindWebService::className(),
                           'user_id'     => '',//MAXMIND_USER_ID
                           'license_key' => '',//MAXMIND_LICENSE_KEY
                           'locales'     => ['cn'],
                       ],
                       'ipApi'           => [
                           'class'          => \ailiangkuai\yii2\GeoIP\Services\IPApi::className(),
                           'secure'         => true,
                           'key'            => '',//IPAPI_KEY
                           'continent_path' => \Yii::getAlias('@runtime/backend/continents.json'),
                       ],
                   ],
               ],
    ],
];
```
