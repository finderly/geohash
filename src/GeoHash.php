<?php
namespace Lvht;

class GeoHash
{
    /**
     * A box accurate to 5,009.4km x 4,992.6km (longitude x latitude)
     */
    const GEOHASH_PRECISION_LOW_KM_5009_X_4992 = 1;
    /**
     * A box accurate to 1,252.3km x 624.1km (longitude x latitude)
     */
    const GEOHASH_PRECISION_LOW_KM_1252_X_624 = 2;
    /**
     * A box accurate to 156.5km x 156km (longitude x latitude)
     */
    const GEOHASH_PRECISION_LOW_KM_156_X_156 = 3;
    /**
     * A box accurate to 39.1km x 19.5km (longitude x latitude)
     */
    const GEOHASH_PRECISION_LOW_KM_39_X_19 = 4;
    /**
     * A box accurate to 4.9km x 4.9km (longitude x latitude)
     */
    const GEOHASH_PRECISION_LOW_KM_5_X_5 = 5;
    /**
     * A box accurate to 1.2km x 609.4m (longitude x latitude)
     */
    const GEOHASH_PRECISION_MEDIUM_M_1200_X_609 = 6;
    /**
     * A box accurate to 152.9m x 152.4m (longitude x latitude)
     */
    const GEOHASH_PRECISION_MEDIUM_M_152_X_152 = 7;
    /**
     * A box accurate to 38.2m x 19m (longitude x latitude)
     */
    const GEOHASH_PRECISION_MEDIUM_M_38_X_19 = 8;
    /**
     * A box accurate to 4.8m x 4.8m (longitude x latitude)
     */
    const GEOHASH_PRECISION_MEDIUM_M_4_X_4 = 9;
    /**
     * A box accurate to 1.2m x 59.5cm (longitude x latitude)
     */
    const GEOHASH_PRECISION_HIGH_CM_120_X_60 = 10;
    /**
     * A box accurate to 14.9cm x 14.9cm (longitude x latitude)
     */
    const GEOHASH_PRECISION_HIGH_CM_14_X_14 = 11;
    /**
     * A box accurate to 3.7cm x 1.9cm (longitude x latitude)
     */
    const GEOHASH_PRECISION_HIGH_CM_3_X_1 = 12;

    private static $table = "0123456789bcdefghjkmnpqrstuvwxyz";
    private static $bits = array(
        0b10000, 0b01000, 0b00100, 0b00010, 0b00001
    );

    /**
     * Encode latitude and longitude to geohash
     *
     * @param float $lng
     * @param float $lat
     * @param integer $prec Precision of the geohash. Minimum 1, maximum 12.
     * @return string
     */
    public static function encode($lng, $lat, $prec = 12)
    {
        $minlng = -180;
        $maxlng = 180;
        $minlat = -90;
        $maxlat = 90;

        $hash = '';
        $isEven = true;
        $chr = 0b00000;
        $b = 0;

        while (strlen($hash) < $prec) {
            if ($isEven) {
                $next = ($minlng + $maxlng) / 2;
                if ($lng > $next) {
                    $chr |= self::$bits[$b];
                    $minlng = $next;
                } else {
                    $maxlng = $next;
                }
            } else {
                $next = ($minlat + $maxlat) / 2;
                if ($lat > $next) {
                    $chr |= self::$bits[$b];
                    $minlat = $next;
                } else {
                    $maxlat = $next;
                }
            }
            $isEven = !$isEven;

            if (++$b == 5) {
                $hash .= self::$table[$chr];
                $b = 0;
                $chr = 0b00000;
            }
        }

        return $hash;
    }

    /**
     * Get all 8 neighbor cells of a specific geohash
     *
     * @param $hash
     * @return array
     */
    public static function expand($hash)
    {
        list($minlng, $maxlng, $minlat, $maxlat) = self::decode($hash);
        $dlng = ($maxlng - $minlng) / 2;
        $dlat = ($maxlat - $minlat) / 2;

        $prec = strlen($hash);
        return array(
            self::encode($minlng - $dlng, $maxlat + $dlat, $prec),
            self::encode($minlng + $dlng, $maxlat + $dlat, $prec),
            self::encode($maxlng + $dlng, $maxlat + $dlat, $prec),
            self::encode($minlng - $dlng, $maxlat - $dlat, $prec),
            self::encode($maxlng + $dlng, $maxlat - $dlat, $prec),
            self::encode($minlng - $dlng, $minlat - $dlat, $prec),
            self::encode($minlng + $dlng, $minlat - $dlat, $prec),
            self::encode($maxlng + $dlng, $minlat - $dlat, $prec),
        );
    }

    public static function getRect($hash)
    {
        list($minlng, $maxlng, $minlat, $maxlat) = self::decode($hash);

        return array(
            array($minlng, $minlat),
            array($minlng, $maxlat),
            array($maxlng, $maxlat),
            array($maxlng, $minlat),
        );
    }

    /**
     * decode a geohash string to a geographical area
     *
     * @var $hash string geohash
     * @return array array($minlng, $maxlng, $minlat, $maxlat);
     */
    public static function decode($hash)
    {
        $minlng = -180;
        $maxlng = 180;
        $minlat = -90;
        $maxlat = 90;

        for ($i=0,$c=strlen($hash); $i<$c; $i++) {
            $v = strpos(self::$table, $hash[$i]);
            if (1&$i) {
                if (16&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (8&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (4&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (2&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (1&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
            } else {
                if (16&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (8&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (4&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (2&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (1&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
            }
        }

        return array($minlng, $maxlng, $minlat, $maxlat);
    }
}
