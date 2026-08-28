<?php

namespace App;

class Geo
{
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Great-circle distance between two coordinates, in kilometers.
     */
    public static function distanceKm(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
    {
        $latDelta = deg2rad($latitude2 - $latitude1);
        $lngDelta = deg2rad($longitude2 - $longitude1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($lngDelta / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
