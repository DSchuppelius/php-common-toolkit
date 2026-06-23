<?php
/*
 * Created on   : Tue Jun 23 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GeoHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Geo;

use InvalidArgumentException;

/**
 * Reine Geo-Berechnungen ohne Netzwerkzugriff (im Gegensatz zum
 * {@see GeocodingHelper}, der eine Geocoding-API anspricht).
 */
final class GeoHelper {
    /** Mittlerer Erdradius in Kilometern. */
    public const EARTH_RADIUS_KM = 6371.0;

    /** Skalierungsfaktor des E7-Koordinatenformats. */
    public const E7_FACTOR = 10_000_000;

    /**
     * Prüft, ob ein Koordinatenpaar innerhalb der gültigen Wertebereiche liegt.
     */
    public static function isValidCoordinate(float $lat, float $lon): bool {
        return is_finite($lat)
            && is_finite($lon)
            && $lat >= -90.0
            && $lat <= 90.0
            && $lon >= -180.0
            && $lon <= 180.0;
    }

    /**
     * Großkreis-Distanz zwischen zwei Punkten (Haversine-Formel) in Kilometern.
     *
     * @param float $lat1 Breitengrad Punkt 1 (Dezimalgrad).
     * @param float $lon1 Längengrad Punkt 1 (Dezimalgrad).
     * @param float $lat2 Breitengrad Punkt 2 (Dezimalgrad).
     * @param float $lon2 Längengrad Punkt 2 (Dezimalgrad).
     * @return float Distanz in Kilometern.
     */
    public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $a = max(0.0, min(1.0, $a));

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Großkreis-Distanz zwischen zwei Punkten in Metern.
     */
    public static function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float {
        return self::haversineKm($lat1, $lon1, $lat2, $lon2) * 1000.0;
    }

    /**
     * Berechnet die Gesamtlänge einer Folge von Koordinaten in Kilometern.
     *
     * Für assoziative Punkte werden standardmäßig die Schlüssel "lat" und "lon"
     * verwendet. Für GeoJSON-Koordinaten kann z.B. $latKey = 1 und $lonKey = 0
     * übergeben werden.
     *
     * @param iterable<array-key, array<array-key, int|float>> $points
     * @param int|string $latKey
     * @param int|string $lonKey
     */
    public static function trackLengthKm(iterable $points, int|string $latKey = 'lat', int|string $lonKey = 'lon'): float {
        $total = 0.0;
        $previous = null;

        foreach ($points as $point) {
            [$lat, $lon] = self::coordinateFromPoint($point, $latKey, $lonKey);

            if ($previous !== null) {
                $total += self::haversineKm($previous[0], $previous[1], $lat, $lon);
            }

            $previous = [$lat, $lon];
        }

        return $total;
    }

    /**
     * Berechnet die Anfangspeilung von Punkt 1 zu Punkt 2 in Grad (0 bis < 360).
     */
    public static function bearingDegrees(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLon = deg2rad($lon2 - $lon1);

        $y = sin($dLon) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad)
            - sin($lat1Rad) * cos($lat2Rad) * cos($dLon);

        return fmod(rad2deg(atan2($y, $x)) + 360.0, 360.0);
    }

    /**
     * Berechnet den geografischen Mittelpunkt zweier Koordinaten.
     *
     * @return array{lat: float, lon: float}
     */
    public static function midpoint(float $lat1, float $lon1, float $lat2, float $lon2): array {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLon = deg2rad($lon2 - $lon1);

        $x = cos($lat2Rad) * cos($dLon);
        $y = cos($lat2Rad) * sin($dLon);

        $lat = atan2(
            sin($lat1Rad) + sin($lat2Rad),
            sqrt((cos($lat1Rad) + $x) ** 2 + $y ** 2)
        );
        $lon = deg2rad($lon1) + atan2($y, cos($lat1Rad) + $x);

        return [
            'lat' => rad2deg($lat),
            'lon' => self::normalizeLongitude(rad2deg($lon)),
        ];
    }

    /**
     * Ermittelt den umschließenden Koordinatenbereich einer Punktfolge.
     *
     * @param iterable<array-key, array<array-key, int|float>> $points
     * @param int|string $latKey
     * @param int|string $lonKey
     * @return array{minLat: float, maxLat: float, minLon: float, maxLon: float}
     */
    public static function boundingBox(iterable $points, int|string $latKey = 'lat', int|string $lonKey = 'lon'): array {
        $bounds = null;

        foreach ($points as $point) {
            [$lat, $lon] = self::coordinateFromPoint($point, $latKey, $lonKey);

            if ($bounds === null) {
                $bounds = [
                    'minLat' => $lat,
                    'maxLat' => $lat,
                    'minLon' => $lon,
                    'maxLon' => $lon,
                ];
                continue;
            }

            $bounds['minLat'] = min($bounds['minLat'], $lat);
            $bounds['maxLat'] = max($bounds['maxLat'], $lat);
            $bounds['minLon'] = min($bounds['minLon'], $lon);
            $bounds['maxLon'] = max($bounds['maxLon'], $lon);
        }

        if ($bounds === null) {
            throw new InvalidArgumentException('Für eine Bounding Box wird mindestens ein Punkt benötigt.');
        }

        return $bounds;
    }

    /**
     * Wandelt Koordinaten aus dem E7-Ganzzahlformat in Dezimalgrad um.
     *
     * @return array{lat: float, lon: float}
     */
    public static function fromE7(int $latE7, int $lonE7): array {
        return [
            'lat' => $latE7 / self::E7_FACTOR,
            'lon' => $lonE7 / self::E7_FACTOR,
        ];
    }

    /**
     * Wandelt Koordinaten aus Dezimalgrad in das E7-Ganzzahlformat um.
     *
     * @return array{lat: int, lon: int}
     */
    public static function toE7(float $lat, float $lon): array {
        return [
            'lat' => (int) round($lat * self::E7_FACTOR),
            'lon' => (int) round($lon * self::E7_FACTOR),
        ];
    }

    /**
     * Formatiert ein Koordinatenpaar als "lat, lon" mit fester Nachkommastellenzahl.
     *
     * @param float $lat Breitengrad (Dezimalgrad).
     * @param float $lon Längengrad (Dezimalgrad).
     * @param int $precision Anzahl Nachkommastellen (Standard: 5).
     * @return string z.B. "52.51630, 13.37770".
     */
    public static function formatCoordinates(float $lat, float $lon, int $precision = 5): string {
        return self::formatLatLon($lat, $lon, $precision);
    }

    /**
     * Formatiert ein Koordinatenpaar in der Reihenfolge Breitengrad, Längengrad.
     */
    public static function formatLatLon(float $lat, float $lon, int $precision = 5): string {
        return sprintf("%.{$precision}f, %.{$precision}f", $lat, $lon);
    }

    /**
     * Formatiert ein Koordinatenpaar in der Reihenfolge Längengrad, Breitengrad.
     */
    public static function formatLonLat(float $lat, float $lon, int $precision = 5): string {
        return sprintf("%.{$precision}f, %.{$precision}f", $lon, $lat);
    }

    /**
     * @param array<array-key, int|float> $point
     * @return array{float, float}
     */
    private static function coordinateFromPoint(array $point, int|string $latKey, int|string $lonKey): array {
        if (!array_key_exists($latKey, $point) || !array_key_exists($lonKey, $point)) {
            throw new InvalidArgumentException(
                sprintf('Punkt benötigt die Koordinatenschlüssel "%s" und "%s".', $latKey, $lonKey)
            );
        }

        $lat = (float) $point[$latKey];
        $lon = (float) $point[$lonKey];

        if (!self::isValidCoordinate($lat, $lon)) {
            throw new InvalidArgumentException(sprintf('Ungültige Koordinate: %s, %s.', $lat, $lon));
        }

        return [$lat, $lon];
    }

    private static function normalizeLongitude(float $lon): float {
        return fmod($lon + 540.0, 360.0) - 180.0;
    }
}
