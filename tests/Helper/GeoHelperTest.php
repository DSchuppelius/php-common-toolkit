<?php
/*
 * Created on   : Tue Jun 23 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GeoHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Helper\Geo\GeoHelper;
use PHPUnit\Framework\TestCase;

final class GeoHelperTest extends TestCase {
    public function test_validates_coordinates(): void {
        $this->assertTrue(GeoHelper::isValidCoordinate(52.5163, 13.3777));
        $this->assertTrue(GeoHelper::isValidCoordinate(-90.0, 180.0));
        $this->assertFalse(GeoHelper::isValidCoordinate(90.0001, 0.0));
        $this->assertFalse(GeoHelper::isValidCoordinate(0.0, -180.0001));
        $this->assertFalse(GeoHelper::isValidCoordinate(INF, 0.0));
    }

    public function test_calculates_haversine_distance_in_kilometers_and_meters(): void {
        $kilometers = GeoHelper::haversineKm(52.5163, 13.3777, 48.137154, 11.576124);

        $this->assertEqualsWithDelta(504.0, $kilometers, 1.0);
        $this->assertEqualsWithDelta($kilometers * 1000.0, GeoHelper::haversineMeters(
            52.5163,
            13.3777,
            48.137154,
            11.576124
        ), 1e-9);
        $this->assertSame(0.0, GeoHelper::haversineKm(52.5163, 13.3777, 52.5163, 13.3777));
    }

    public function test_calculates_track_length_for_associative_and_indexed_points(): void {
        $associative = [
            ['lat' => 52.5163, 'lon' => 13.3777],
            ['lat' => 51.0504, 'lon' => 13.7373],
            ['lat' => 50.1109, 'lon' => 8.6821],
        ];
        $indexed = array_map(
            static fn (array $point): array => [$point['lon'], $point['lat']],
            $associative
        );

        $this->assertGreaterThan(500.0, GeoHelper::trackLengthKm($associative));
        $this->assertEqualsWithDelta(
            GeoHelper::trackLengthKm($associative),
            GeoHelper::trackLengthKm($indexed, 1, 0),
            1e-9
        );
        $this->assertSame(0.0, GeoHelper::trackLengthKm([]));
    }

    public function test_rejects_invalid_track_point(): void {
        $this->expectException(InvalidArgumentException::class);

        GeoHelper::trackLengthKm([['lat' => 91.0, 'lon' => 13.0]]);
    }

    public function test_calculates_bearing(): void {
        $this->assertEqualsWithDelta(90.0, GeoHelper::bearingDegrees(0.0, 0.0, 0.0, 1.0), 1e-9);
        $this->assertEqualsWithDelta(0.0, GeoHelper::bearingDegrees(0.0, 0.0, 1.0, 0.0), 1e-9);
    }

    public function test_calculates_midpoint_across_date_line(): void {
        $midpoint = GeoHelper::midpoint(10.0, 170.0, 10.0, -170.0);

        $this->assertEqualsWithDelta(10.1511, $midpoint['lat'], 0.001);
        $this->assertEqualsWithDelta(-180.0, $midpoint['lon'], 1e-9);
    }

    public function test_calculates_bounding_box(): void {
        $bounds = GeoHelper::boundingBox([
            ['lat' => 48.1, 'lon' => 11.5],
            ['lat' => 52.5, 'lon' => 13.4],
            ['lat' => 50.1, 'lon' => 8.6],
        ]);

        $this->assertSame([
            'minLat' => 48.1,
            'maxLat' => 52.5,
            'minLon' => 8.6,
            'maxLon' => 13.4,
        ], $bounds);
    }

    public function test_rejects_empty_bounding_box(): void {
        $this->expectException(InvalidArgumentException::class);

        GeoHelper::boundingBox([]);
    }

    public function test_converts_e7_coordinates(): void {
        $decimal = GeoHelper::fromE7(525163000, 133777000);

        $this->assertSame(['lat' => 52.5163, 'lon' => 13.3777], $decimal);
        $this->assertSame(['lat' => 525163000, 'lon' => 133777000], GeoHelper::toE7(
            $decimal['lat'],
            $decimal['lon']
        ));
    }

    public function test_formats_both_coordinate_orders(): void {
        $this->assertSame('52.51630, 13.37770', GeoHelper::formatCoordinates(52.5163, 13.3777));
        $this->assertSame('52.51630, 13.37770', GeoHelper::formatLatLon(52.5163, 13.3777));
        $this->assertSame('13.37770, 52.51630', GeoHelper::formatLonLat(52.5163, 13.3777));
    }
}
