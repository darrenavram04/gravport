<?php

declare(strict_types=1);

use App\Controllers\Catalog;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;

final class CatalogWhiteBoxTest extends CIUnitTestCase
{
    private function invokePrivate(object $target, string $method, array $arguments = []): mixed
    {
        $r = new ReflectionMethod($target, $method);
        $r->setAccessible(true);

        return $r->invokeArgs($target, $arguments);
    }

    // -------------------------------------------------------------------------
    // safeFilename
    // -------------------------------------------------------------------------

    public function testSafeFilenameConvertsSpacesToUnderscores(): void
    {
        $c = new Catalog();
        $this->assertSame('free_air_anomaly_level_1', $this->invokePrivate($c, 'safeFilename', ['Free Air Anomaly Level 1']));
    }

    public function testSafeFilenamePreservesAlphanumericAndUnderscores(): void
    {
        $c = new Catalog();
        $this->assertSame('faa_l1_data', $this->invokePrivate($c, 'safeFilename', ['faa_l1_data']));
    }

    public function testSafeFilenameTrimsLeadingTrailingUnderscoresAfterNormalization(): void
    {
        $c = new Catalog();
        $this->assertSame('faa', $this->invokePrivate($c, 'safeFilename', ['__FAA__']));
    }

    public function testSafeFilenameReturnsDatasetFallbackForEmptyInput(): void
    {
        $c = new Catalog();
        $this->assertSame('dataset', $this->invokePrivate($c, 'safeFilename', ['']));
    }

    public function testSafeFilenameReturnsDatasetFallbackForAllSpecialChars(): void
    {
        $c = new Catalog();
        $this->assertSame('dataset', $this->invokePrivate($c, 'safeFilename', ['!@#$%^&*()']));
    }

    // -------------------------------------------------------------------------
    // shouldAggregateVectorPreview — 5 independent decision paths
    // -------------------------------------------------------------------------

    public function testShouldAggregateVectorPreviewReturnsTrueForLowZoom(): void
    {
        $c = new Catalog();
        // zoom ≤ 7 → aggregate regardless of bounds
        $this->assertTrue($this->invokePrivate($c, 'shouldAggregateVectorPreview', [
            ['west' => 107.0, 'south' => -7.0, 'east' => 108.0, 'north' => -6.0],
            7,
        ]));
    }

    public function testShouldAggregateVectorPreviewReturnsTrueWhenBoundsAreNull(): void
    {
        $c = new Catalog();
        // null bounds → always aggregate (can't compute span)
        $this->assertTrue($this->invokePrivate($c, 'shouldAggregateVectorPreview', [null, 12]));
    }

    public function testShouldAggregateVectorPreviewReturnsFalseForHighZoom(): void
    {
        $c = new Catalog();
        // zoom > 9 → never aggregate
        $this->assertFalse($this->invokePrivate($c, 'shouldAggregateVectorPreview', [
            ['west' => 107.0, 'south' => -7.0, 'east' => 108.0, 'north' => -6.0],
            10,
        ]));
    }

    public function testShouldAggregateVectorPreviewReturnsTrueForMidZoomLargeSpan(): void
    {
        $c = new Catalog();
        // zoom 9, span = 2 deg (≥ 1.2) → aggregate
        $this->assertTrue($this->invokePrivate($c, 'shouldAggregateVectorPreview', [
            ['west' => 106.0, 'south' => -8.0, 'east' => 108.0, 'north' => -6.0],
            9,
        ]));
    }

    public function testShouldAggregateVectorPreviewReturnsFalseForMidZoomSmallSpan(): void
    {
        $c = new Catalog();
        // zoom 9, span = 0.5 deg (< 1.2) → no aggregate
        $this->assertFalse($this->invokePrivate($c, 'shouldAggregateVectorPreview', [
            ['west' => 107.0, 'south' => -7.2, 'east' => 107.5, 'north' => -7.0],
            9,
        ]));
    }

    // -------------------------------------------------------------------------
    // aggregateGridSize — zoom presets + dynamic bounds override + clamp
    // -------------------------------------------------------------------------

    public function testAggregateGridSizeZoom5Preset(): void
    {
        $c = new Catalog();
        $this->assertSame(0.35, $this->invokePrivate($c, 'aggregateGridSize', [null, 5]));
    }

    public function testAggregateGridSizeZoom6Preset(): void
    {
        $c = new Catalog();
        $this->assertSame(0.22, $this->invokePrivate($c, 'aggregateGridSize', [null, 6]));
    }

    public function testAggregateGridSizeZoom7Preset(): void
    {
        $c = new Catalog();
        $this->assertSame(0.14, $this->invokePrivate($c, 'aggregateGridSize', [null, 7]));
    }

    public function testAggregateGridSizeZoom8Preset(): void
    {
        $c = new Catalog();
        $this->assertSame(0.08, $this->invokePrivate($c, 'aggregateGridSize', [null, 8]));
    }

    public function testAggregateGridSizeHighZoomDefaultPreset(): void
    {
        $c = new Catalog();
        $this->assertSame(0.04, $this->invokePrivate($c, 'aggregateGridSize', [null, 11]));
    }

    public function testAggregateGridSizeClampedToMaxByVeryWideBounds(): void
    {
        $c = new Catalog();
        // bounds span 26 deg wide × 17 deg tall → dynamic >> preset → clamped to 0.45
        $result = $this->invokePrivate($c, 'aggregateGridSize', [
            ['west' => 95.0, 'south' => -11.0, 'east' => 121.0, 'north' => 6.0],
            6,
        ]);
        $this->assertSame(0.45, $result);
    }

    public function testAggregateGridSizePresetWinsOverSmallBoundsDynamic(): void
    {
        $c = new Catalog();
        // zoom 8 preset = 0.08; small bounds → dynamic ≈ 0.004 (less than preset)
        $result = $this->invokePrivate($c, 'aggregateGridSize', [
            ['west' => 107.0, 'south' => -7.1, 'east' => 107.1, 'north' => -7.0],
            8,
        ]);
        $this->assertSame(0.08, $result);
    }

    // -------------------------------------------------------------------------
    // decodeBytea
    // -------------------------------------------------------------------------

    public function testDecodeByteaConvertsHexPrefixedString(): void
    {
        $c = new Catalog();
        // \x48656c6c6f = "Hello"
        $result = $this->invokePrivate($c, 'decodeBytea', ['\\x48656c6c6f']);
        $this->assertSame('Hello', $result);
    }

    public function testDecodeByteaReturnsEmptyStringForInvalidOddLengthHex(): void
    {
        $c = new Catalog();
        // odd-length hex (3 hex chars) → hex2bin() returns false → must return ''
        $result = $this->invokePrivate($c, 'decodeBytea', ['\\xABC']);
        $this->assertSame('', $result);
    }

    // -------------------------------------------------------------------------
    // entrySpatialSql — 4 combination paths
    // -------------------------------------------------------------------------

    public function testEntrySpatialSqlReturnsEmptyWhenNoFilters(): void
    {
        $c = new Catalog();
        [$sql, $params] = $this->invokePrivate($c, 'entrySpatialSql', [
            't.geom',
            ['province_id' => null],
            null,
        ]);
        $this->assertSame('', $sql);
        $this->assertSame([], $params);
    }

    public function testEntrySpatialSqlEmbedsProvinceIdAsIntInSubquery(): void
    {
        $c = new Catalog();
        [$sql, $params] = $this->invokePrivate($c, 'entrySpatialSql', [
            't.geom',
            ['province_id' => 7],
            null,
        ]);
        $this->assertStringContainsString('ST_Intersects', $sql);
        $this->assertStringContainsString('id = 7', $sql);
        $this->assertSame([], $params); // province_id embedded as int, not a bind param
    }

    public function testEntrySpatialSqlAddsBoundsAsBindParams(): void
    {
        $c = new Catalog();
        [$sql, $params] = $this->invokePrivate($c, 'entrySpatialSql', [
            't.geom',
            ['province_id' => null],
            ['west' => 107.0, 'south' => -7.0, 'east' => 108.0, 'north' => -6.0],
        ]);
        $this->assertStringContainsString('ST_MakeEnvelope', $sql);
        $this->assertSame([107.0, -7.0, 108.0, -6.0], $params);
    }

    public function testEntrySpatialSqlCombinesProvinceAndBoundsWithAnd(): void
    {
        $c = new Catalog();
        [$sql, $params] = $this->invokePrivate($c, 'entrySpatialSql', [
            't.geom',
            ['province_id' => 3],
            ['west' => 107.0, 'south' => -7.0, 'east' => 108.0, 'north' => -6.0],
        ]);
        $this->assertStringContainsString('id = 3', $sql);
        $this->assertStringContainsString('ST_MakeEnvelope', $sql);
        $this->assertStringContainsString(' AND ', $sql);
        $this->assertSame([107.0, -7.0, 108.0, -6.0], $params);
    }

    // -------------------------------------------------------------------------
    // boundsFromRequest — parses bbox GET param
    // -------------------------------------------------------------------------

    private function catalogWithGet(array $getParams): Catalog
    {
        $request = new IncomingRequest(
            config('App'),
            new URI('http://localhost/'),
            null,
            new UserAgent()
        );
        $request->setGlobal('get', $getParams);

        $c = new Catalog();
        $c->initController($request, service('response'), service('logger'));

        return $c;
    }

    public function testBoundsFromRequestParsesValidFourPartCsv(): void
    {
        $c = $this->catalogWithGet(['bbox' => '107,-7,108,-6']);
        $result = $this->invokePrivate($c, 'boundsFromRequest', []);

        $this->assertSame([
            'west'  => 107.0,
            'south' => -7.0,
            'east'  => 108.0,
            'north' => -6.0,
        ], $result);
    }

    public function testBoundsFromRequestReturnsNullWhenBboxAbsent(): void
    {
        $c = $this->catalogWithGet([]);
        $result = $this->invokePrivate($c, 'boundsFromRequest', []);

        $this->assertNull($result);
    }

    public function testBoundsFromRequestReturnsNullForNonNumericPart(): void
    {
        $c = $this->catalogWithGet(['bbox' => '107,abc,108,-6']);
        $result = $this->invokePrivate($c, 'boundsFromRequest', []);

        $this->assertNull($result);
    }

    public function testBoundsFromRequestReturnsNullForWrongPartCount(): void
    {
        $c = $this->catalogWithGet(['bbox' => '107,-7,108']);
        $result = $this->invokePrivate($c, 'boundsFromRequest', []);

        $this->assertNull($result);
    }

    public function testBoundsFromRequestReturnsNullForInvertedWestEast(): void
    {
        $c = $this->catalogWithGet(['bbox' => '108,-7,107,-6']);
        $result = $this->invokePrivate($c, 'boundsFromRequest', []);

        $this->assertNull($result);
    }

    public function testBoundsFromRequestRejectsOutOfRangeCoordinates(): void
    {
        $c = $this->catalogWithGet(['bbox' => '200,-95,201,-94']);
        $result = $this->invokePrivate($c, 'boundsFromRequest', []);

        $this->assertNull($result, 'Out-of-range bbox must be rejected by boundsFromRequest().');
    }
}
