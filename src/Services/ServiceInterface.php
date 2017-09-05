<?php

namespace ailiangkuai\yii2\GeoIP\Services;

interface ServiceInterface
{
    /**
     * The "booting" method of the service.
     *
     * @return void
     */
    public function boot();

    /**
     * Determine a location based off of
     * the provided IP address.
     *
     * @param string $ip
     *
     * @return \ailiangkuai\yii2\GeoIP\Location
     */
    public function locate($ip);

    /**
     * Create a location instance from the provided attributes.
     *
     * @param array $attributes
     *
     * @return \ailiangkuai\yii2\GeoIP\Location
     */
    public function hydrate(array $attributes = []);

}