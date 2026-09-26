<?php

namespace App\Modules\Attendance\Support;

final class Geo
{
    /** Distance in metres between two points on the Earth (haversine). */
    public static function distanceMetres(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $r = 6_371_000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
