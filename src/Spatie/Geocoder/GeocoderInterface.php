<?php namespace Spatie\Geocoder;

interface GeocoderInterface {

    /**
     *
     * Get the coordinates for a query
     *
     * @param string $query
     * @return array
     */
    public function getCoordinatesForQuery($query);
}