<?php

declare(strict_types=1);

use App\Controllers\WebMap;
use CodeIgniter\Test\CIUnitTestCase;

final class WebMapWhiteBoxTest extends CIUnitTestCase
{
    private function invokePrivate(object $target, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }

    public function testNormalizeBoundsAcceptsCsvString(): void
    {
        $webMap = new WebMap();

        $result = $this->invokePrivate($webMap, 'normalizeBounds', ['107.1,-7.4,108.2,-6.8']);

        $this->assertSame([
            'west' => 107.1,
            'south' => -7.4,
            'east' => 108.2,
            'north' => -6.8,
        ], $result);
    }

    public function testNormalizeBoundsRejectsInvertedBounds(): void
    {
        $webMap = new WebMap();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bounds harus memiliki west < east dan south < north.');

        $this->invokePrivate($webMap, 'normalizeBounds', [[
            'west' => 108,
            'south' => -6,
            'east' => 107,
            'north' => -7,
        ]]);
    }

    public function testNormalizeBoundsRejectsOutOfRangeCoordinates(): void
    {
        $webMap = new WebMap();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bounds berada di luar rentang koordinat geografis yang valid.');

        $this->invokePrivate($webMap, 'normalizeBounds', [[
            'west' => 200,
            'south' => -95,
            'east' => 201,
            'north' => -94,
        ]]);
    }

    public function testFiltersFromInputClampsNegativeBufferAndParsesFlags(): void
    {
        $webMap = new WebMap();

        $filters = $this->invokePrivate($webMap, 'filtersFromInput', [[
            'province_id' => '12',
            'buffer_meters' => '-50',
            'bounds' => ['west' => '107', 'south' => '-7', 'east' => '108', 'north' => '-6'],
            'zoom' => '9',
            'force_detail' => 'true',
        ]]);

        $this->assertSame(12, $filters['province_id']);
        $this->assertSame(0, $filters['buffer_meters']);
        $this->assertSame(9, $filters['zoom']);
        $this->assertTrue($filters['force_detail']);
        $this->assertSame([
            'west' => 107.0,
            'south' => -7.0,
            'east' => 108.0,
            'north' => -6.0,
        ], $filters['bounds']);
    }

    public function testShouldAggregateVectorFollowsIndependentDecisionPaths(): void
    {
        $webMap = new WebMap();

        $this->assertFalse($this->invokePrivate($webMap, 'shouldAggregateVector', [[
            'force_detail' => true,
            'zoom' => 4,
        ]]));

        $this->assertTrue($this->invokePrivate($webMap, 'shouldAggregateVector', [[
            'zoom' => 7,
            'bounds' => ['west' => 107, 'south' => -7, 'east' => 108, 'north' => -6],
        ]]));

        $this->assertTrue($this->invokePrivate($webMap, 'shouldAggregateVector', [[
            'zoom' => 9,
            'bounds' => ['west' => 106, 'south' => -8, 'east' => 108, 'north' => -6],
        ]]));

        $this->assertFalse($this->invokePrivate($webMap, 'shouldAggregateVector', [[
            'zoom' => 10,
            'bounds' => ['west' => 107.0, 'south' => -7.2, 'east' => 107.3, 'north' => -7.0],
        ]]));
    }

    public function testAggregateGridSizeUsesPresetAndClamp(): void
    {
        $webMap = new WebMap();

        $this->assertSame(0.08, $this->invokePrivate($webMap, 'aggregateGridSize', [[
            'zoom' => 8,
        ]]));

        $this->assertSame(0.45, $this->invokePrivate($webMap, 'aggregateGridSize', [[
            'zoom' => 6,
            'bounds' => ['west' => 95, 'south' => -11, 'east' => 120, 'north' => 6],
        ]]));
    }

    public function testBoundaryPayloadConvertsFeatureCollectionToGeometryCollection(): void
    {
        $webMap = new WebMap();

        $payload = $this->invokePrivate($webMap, 'boundaryPayload', [[
            'geometry' => [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [107.6, -6.9],
                        ],
                    ],
                    [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Polygon',
                            'coordinates' => [[[107, -7], [108, -7], [108, -6], [107, -6], [107, -7]]],
                        ],
                    ],
                ],
            ],
        ]]);

        $geometry = json_decode((string) $payload['geojson'], true);

        $this->assertSame('geometry', $payload['source']);
        $this->assertSame('GeometryCollection', $payload['geometry_type']);
        $this->assertSame('GeometryCollection', $geometry['type']);
        $this->assertCount(2, $geometry['geometries']);
    }

    public function testBoundaryPayloadBuildsBoundsPolygon(): void
    {
        $webMap = new WebMap();

        $payload = $this->invokePrivate($webMap, 'boundaryPayload', [[
            'bounds' => ['west' => 107, 'south' => -7, 'east' => 108, 'north' => -6],
        ]]);

        $geometry = json_decode((string) $payload['geojson'], true);

        $this->assertSame('bounds', $payload['source']);
        $this->assertSame('Polygon', $payload['geometry_type']);
        $this->assertEquals([107.0, -7.0], $geometry['coordinates'][0][0]);
        $this->assertEquals([107.0, -7.0], $geometry['coordinates'][0][4]);
    }

    public function testSpatialSqlUsesPointBufferAndViewportGuard(): void
    {
        $webMap = new WebMap();

        [$sql, $params] = $this->invokePrivate($webMap, 'spatialSql', [
            't.geom',
            [
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [107.6, -6.9],
                ],
                'buffer_meters' => 1500,
                'bounds' => ['west' => 107.4, 'south' => -7.1, 'east' => 107.8, 'north' => -6.7],
            ],
        ]);

        $this->assertStringContainsString('ST_DWithin', $sql);
        $this->assertStringContainsString('ST_MakeEnvelope', $sql);
        $this->assertSame(6, count($params));
        $this->assertSame(1500, $params[1]);
        $this->assertSame(107.4, $params[2]);
        $this->assertSame(-6.7, $params[5]);
    }

    public function testBoundaryExpressionBuffersPointInMeters(): void
    {
        $webMap = new WebMap();

        [$expression, $params] = $this->invokePrivate($webMap, 'boundaryExpression', [
            [
                'geojson' => '{"type":"Point","coordinates":[107.6,-6.9]}',
                'geometry_type' => 'Point',
            ],
            ['buffer_meters' => 2500],
        ]);

        $this->assertStringContainsString('ST_Buffer', $expression);
        $this->assertSame('{"type":"Point","coordinates":[107.6,-6.9]}', $params[0]);
        $this->assertSame(2500, $params[1]);
    }

    public function testMetadataScopePrioritizesProvinceThenGeometryThenBounds(): void
    {
        $webMap = new WebMap();

        $this->assertSame('regional', $this->invokePrivate($webMap, 'metadataScope', [[
            'province_id' => 5,
            'geometry' => ['type' => 'Point'],
            'bounds' => ['west' => 1, 'south' => 2, 'east' => 3, 'north' => 4],
        ]]));

        $this->assertSame('custom', $this->invokePrivate($webMap, 'metadataScope', [[
            'geometry' => ['type' => 'Polygon'],
        ]]));

        $this->assertSame('viewport', $this->invokePrivate($webMap, 'metadataScope', [[
            'bounds' => ['west' => 1, 'south' => 2, 'east' => 3, 'north' => 4],
        ]]));

        $this->assertSame('national', $this->invokePrivate($webMap, 'metadataScope', [[]]));
    }

    // -------------------------------------------------------------------------
    // normalizeBounds — additional edge cases
    // -------------------------------------------------------------------------

    public function testNormalizeBoundsReturnsNullForNull(): void
    {
        $webMap = new WebMap();
        $this->assertNull($this->invokePrivate($webMap, 'normalizeBounds', [null]));
    }

    public function testNormalizeBoundsReturnsNullForEmptyString(): void
    {
        $webMap = new WebMap();
        $this->assertNull($this->invokePrivate($webMap, 'normalizeBounds', ['']));
    }

    public function testNormalizeBoundsAcceptsValidJsonString(): void
    {
        $webMap = new WebMap();
        $result = $this->invokePrivate($webMap, 'normalizeBounds', [
            '{"west":107.1,"south":-7.4,"east":108.2,"north":-6.8}',
        ]);
        $this->assertSame([
            'west'  => 107.1,
            'south' => -7.4,
            'east'  => 108.2,
            'north' => -6.8,
        ], $result);
    }

    public function testNormalizeBoundsThrowsForThreePartCsvString(): void
    {
        $webMap = new WebMap();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format bounds tidak valid.');
        $this->invokePrivate($webMap, 'normalizeBounds', ['107,-7,108']);
    }

    public function testNormalizeBoundsThrowsForMissingNorthKey(): void
    {
        $webMap = new WebMap();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bounds harus berisi west, south, east, dan north numerik.');
        $this->invokePrivate($webMap, 'normalizeBounds', [[
            'west' => 107, 'south' => -7, 'east' => 108,
        ]]);
    }

    public function testNormalizeBoundsThrowsForNonNumericValue(): void
    {
        $webMap = new WebMap();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bounds harus berisi west, south, east, dan north numerik.');
        $this->invokePrivate($webMap, 'normalizeBounds', [[
            'west' => 'abc', 'south' => -7, 'east' => 108, 'north' => -6,
        ]]);
    }

    public function testNormalizeBoundsThrowsForNonArrayNonString(): void
    {
        $webMap = new WebMap();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format bounds tidak valid.');
        $this->invokePrivate($webMap, 'normalizeBounds', [42]);
    }

    // -------------------------------------------------------------------------
    // geometryType — all paths
    // -------------------------------------------------------------------------

    public function testGeometryTypeExtractsTypeFromArray(): void
    {
        $webMap = new WebMap();
        $result = $this->invokePrivate($webMap, 'geometryType', [
            ['type' => 'Point', 'coordinates' => [107.6, -6.9]],
        ]);
        $this->assertSame('Point', $result);
    }

    public function testGeometryTypeUnwrapsFeatureToGetGeometryType(): void
    {
        $webMap = new WebMap();
        $result = $this->invokePrivate($webMap, 'geometryType', [[
            'type'     => 'Feature',
            'geometry' => ['type' => 'Polygon', 'coordinates' => []],
        ]]);
        $this->assertSame('Polygon', $result);
    }

    public function testGeometryTypeReturnsNullForNonArray(): void
    {
        $webMap = new WebMap();
        $this->assertNull($this->invokePrivate($webMap, 'geometryType', [null]));
        $this->assertNull($this->invokePrivate($webMap, 'geometryType', [42]));
    }

    public function testGeometryTypeDecodesJsonString(): void
    {
        $webMap = new WebMap();
        $result = $this->invokePrivate($webMap, 'geometryType', [
            '{"type":"LineString","coordinates":[]}',
        ]);
        $this->assertSame('LineString', $result);
    }

    public function testGeometryTypeReturnsNullForInvalidJsonString(): void
    {
        $webMap = new WebMap();
        $result = $this->invokePrivate($webMap, 'geometryType', ['not-json']);
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // boundaryPayload — additional paths
    // -------------------------------------------------------------------------

    public function testBoundaryPayloadReturnsNullForAllNullFilters(): void
    {
        $webMap = new WebMap();
        $result = $this->invokePrivate($webMap, 'boundaryPayload', [[
            'province_id' => null,
            'geometry'    => null,
            'bounds'      => null,
        ]]);
        $this->assertNull($result);
    }

    public function testBoundaryPayloadUnwrapsFeatureGeometryType(): void
    {
        $webMap = new WebMap();
        $payload = $this->invokePrivate($webMap, 'boundaryPayload', [[
            'geometry' => [
                'type'     => 'Feature',
                'geometry' => ['type' => 'Polygon', 'coordinates' => [[[107, -7], [108, -7], [108, -6], [107, -6], [107, -7]]]],
            ],
        ]]);
        $this->assertSame('geometry', $payload['source']);
        $this->assertSame('Polygon', $payload['geometry_type']);
    }

    public function testBoundaryPayloadThrowsForGeometryMissingType(): void
    {
        $webMap = new WebMap();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Geometri boundary tidak valid.');
        $this->invokePrivate($webMap, 'boundaryPayload', [[
            'geometry' => ['coordinates' => []],  // missing 'type'
        ]]);
    }

    public function testBoundaryPayloadThrowsForInvalidGeoJsonString(): void
    {
        $webMap = new WebMap();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Boundary GeoJSON tidak valid.');
        $this->invokePrivate($webMap, 'boundaryPayload', [[
            'geometry' => 'not-valid-json',
        ]]);
    }

    // -------------------------------------------------------------------------
    // spatialSql — no filters → empty output
    // -------------------------------------------------------------------------

    public function testSpatialSqlReturnsEmptyStringAndParamsWhenNoFilters(): void
    {
        $webMap = new WebMap();
        [$sql, $params] = $this->invokePrivate($webMap, 'spatialSql', [
            't.geom',
            [
                'province_id'    => null,
                'geometry'       => null,
                'buffer_meters'  => 0,
                'bounds'         => null,
                'zoom'           => null,
                'force_detail'   => false,
            ],
        ]);
        $this->assertSame('', $sql);
        $this->assertSame([], $params);
    }

    // -------------------------------------------------------------------------
    // boundaryExpression — non-Point geometry (no buffer applied)
    // -------------------------------------------------------------------------

    public function testBoundaryExpressionUsesSimpleGeomFromGeoJsonForPolygon(): void
    {
        $webMap = new WebMap();
        [$expression, $params] = $this->invokePrivate($webMap, 'boundaryExpression', [
            [
                'geojson'       => '{"type":"Polygon","coordinates":[]}',
                'geometry_type' => 'Polygon',
            ],
            ['buffer_meters' => 500],
        ]);
        // Polygon + buffer_meters should NOT use ST_Buffer (only Point does)
        $this->assertStringNotContainsString('ST_Buffer', $expression);
        $this->assertStringContainsString('ST_GeomFromGeoJSON', $expression);
        $this->assertCount(1, $params);
    }
}
