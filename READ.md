# GeoIp for yii2
yii2的扩展插件，可以根据ip获取ip所在的省市区邮编经纬度
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
## example
```php
\Yii::$app->geoIp->getLocation('192.30.255.112');
//ailiangkuai\yii2\GeoIP\Location Object
//(
//    [attributes:protected] => Array
//        (
//            [ip] => 192.30.255.112
//            [iso_code] => US
//            [country] => 美国
//            [province] => 加利福尼亚州
//            [city] => 旧金山
//            [state] => CA
//            [state_name] => 加利福尼亚州
//            [postal_code] => 94107
//            [lat] => 37.7697
//            [lon] => -122.3933
//            [timezone] => America/Los_Angeles
//            [continent] => NA
//            [currency] => USD
//           [default] => 
//        )
//
//)
```
