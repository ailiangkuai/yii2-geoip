<?php

namespace ailiangkuai\yii2\GeoIP\Services;

use ailiangkuai\yii2\GeoIP\GeoIP;
use ailiangkuai\yii2\GeoIP\Location;
use ailiangkuai\yii2\GeoIP\Services\ServiceInterface;
use yii\base\Object;
use yii\helpers\ArrayHelper;

abstract class AbstractService extends Object implements ServiceInterface
{

    public function init()
    {
        parent::init();
        $this->boot();
    }

    /**
     * The "booting" method of the service.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(array $attributes = [])
    {
        return new Location($attributes);
    }

}