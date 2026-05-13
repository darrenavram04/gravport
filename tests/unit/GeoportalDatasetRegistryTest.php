<?php

declare(strict_types=1);

use App\Libraries\GeoportalDatasetRegistry;
use CodeIgniter\Test\CIUnitTestCase;

final class GeoportalDatasetRegistryTest extends CIUnitTestCase
{
    // -------------------------------------------------------------------------
    // definitions() — static data, no DB
    // -------------------------------------------------------------------------

    public function testDefinitionsReturnsFourDatasets(): void
    {
        $r = new GeoportalDatasetRegistry();
        $this->assertCount(4, $r->definitions());
    }

    public function testDefinitionsContainsExpectedCodes(): void
    {
        $r = new GeoportalDatasetRegistry();
        $codes = array_keys($r->definitions());
        $this->assertSame(['faa_l1', 'cba_l1', 'faa_l2', 'cba_l2'], $codes);
    }

    public function testDefinitionsVectorDatasetsHaveCorrectType(): void
    {
        $r = new GeoportalDatasetRegistry();
        $defs = $r->definitions();
        $this->assertSame('vector', $defs['faa_l1']['type']);
        $this->assertSame('vector', $defs['cba_l1']['type']);
    }

    public function testDefinitionsRasterDatasetsHaveCorrectType(): void
    {
        $r = new GeoportalDatasetRegistry();
        $defs = $r->definitions();
        $this->assertSame('raster', $defs['faa_l2']['type']);
        $this->assertSame('raster', $defs['cba_l2']['type']);
    }

    public function testDefinitionsRasterDatasetsHaveRasterColumnKey(): void
    {
        $r = new GeoportalDatasetRegistry();
        $defs = $r->definitions();
        $this->assertArrayHasKey('raster_column', $defs['faa_l2']);
        $this->assertArrayHasKey('raster_column', $defs['cba_l2']);
    }

    public function testDefinitionsVectorDatasetsDoNotHaveRasterColumnKey(): void
    {
        $r = new GeoportalDatasetRegistry();
        $defs = $r->definitions();
        $this->assertArrayNotHasKey('raster_column', $defs['faa_l1']);
        $this->assertArrayNotHasKey('raster_column', $defs['cba_l1']);
    }

    public function testDefinitionsAllDatasetsHaveSummaryUnitMGal(): void
    {
        $r = new GeoportalDatasetRegistry();
        foreach ($r->definitions() as $code => $def) {
            $this->assertSame('mGal', $def['summary_unit'], "summary_unit mismatch for {$code}");
        }
    }

    public function testDefinitionsAnomalyKeyInferredFromCode(): void
    {
        // anomaly_key is derived in catalogEntryPayload, not in definitions().
        // Verify that faa_* codes start with 'faa_' and cba_* with 'cba_'.
        $r = new GeoportalDatasetRegistry();
        foreach ($r->definitions() as $code => $def) {
            if (str_starts_with($code, 'faa_')) {
                $this->assertStringStartsWith('faa_', $def['code']);
            } else {
                $this->assertStringStartsWith('cba_', $def['code']);
            }
        }
    }

    // -------------------------------------------------------------------------
    // dataset() — no DB required
    // -------------------------------------------------------------------------

    public function testDatasetReturnsFaaL1Definition(): void
    {
        $r = new GeoportalDatasetRegistry();
        $d = $r->dataset('faa_l1');
        $this->assertSame('faa_l1', $d['code']);
        $this->assertSame('vector', $d['type']);
    }

    public function testDatasetReturnsCbaL2Definition(): void
    {
        $r = new GeoportalDatasetRegistry();
        $d = $r->dataset('cba_l2');
        $this->assertSame('cba_l2', $d['code']);
        $this->assertSame('raster', $d['type']);
    }

    public function testDatasetThrowsForUnknownCode(): void
    {
        $r = new GeoportalDatasetRegistry();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dataset tidak terdaftar.');
        $r->dataset('invalid_code');
    }

    public function testDatasetThrowsForEmptyCode(): void
    {
        $r = new GeoportalDatasetRegistry();
        $this->expectException(InvalidArgumentException::class);
        $r->dataset('');
    }

    // -------------------------------------------------------------------------
    // filterCatalogEntries() — requires gravport DB (provinces)
    // -------------------------------------------------------------------------

    public function testFilterCatalogEntriesWithNoFilterReturnsAll(): void
    {
        $r = new GeoportalDatasetRegistry();
        $all = $r->filterCatalogEntries([]);
        // 4 datasets × (1 national + N provinces) — just verify it is non-empty
        $this->assertNotEmpty($all);
    }

    public function testFilterCatalogEntriesSearchByTitle(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries(['q' => 'Free Air Anomaly Level 1']);
        foreach ($results as $entry) {
            $this->assertStringContainsStringIgnoringCase('free air anomaly level 1', (string) $entry['title']);
        }
    }

    public function testFilterCatalogEntriesNationalScopeOnly(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries(['spatial_scope' => ['national']]);
        foreach ($results as $entry) {
            $this->assertSame('national', $entry['spatial_scope']);
        }
        // 4 datasets × 1 national = 4 entries
        $this->assertCount(4, $results);
    }

    public function testFilterCatalogEntriesRegionalScopeContainsProvinceName(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries(['spatial_scope' => ['regional']]);
        foreach ($results as $entry) {
            $this->assertSame('regional', $entry['spatial_scope']);
            $this->assertNotNull($entry['province_name']);
        }
    }

    public function testFilterCatalogEntriesByAnomalyFaaOnly(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries(['anomaly' => ['faa']]);
        foreach ($results as $entry) {
            $this->assertSame('faa', $entry['anomaly_key']);
        }
    }

    public function testFilterCatalogEntriesByCbaOnly(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries(['anomaly' => ['cba']]);
        foreach ($results as $entry) {
            $this->assertSame('cba', $entry['anomaly_key']);
        }
    }

    public function testFilterCatalogEntriesByLevel1Only(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries(['level' => ['level1']]);
        foreach ($results as $entry) {
            $this->assertSame('level1', $entry['level_key']);
        }
    }

    public function testFilterCatalogEntriesByLevel2Only(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries(['level' => ['level2']]);
        foreach ($results as $entry) {
            $this->assertSame('level2', $entry['level_key']);
        }
    }

    public function testFilterCatalogEntriesDownloadableFilterExcludesNone(): void
    {
        $r = new GeoportalDatasetRegistry();
        $all = $r->filterCatalogEntries([]);
        $downloadable = $r->filterCatalogEntries(['downloadable' => true]);
        // All entries are downloadable by default
        $this->assertCount(count($all), $downloadable);
    }

    public function testFilterCatalogEntriesSearchReturnsEmptyForNonexistentTitle(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries(['q' => 'ZZZZNONEXISTENTDATASET9999']);
        $this->assertSame([], $results);
    }

    public function testFilterCatalogEntriesCombinedFaaLevel1National(): void
    {
        $r = new GeoportalDatasetRegistry();
        $results = $r->filterCatalogEntries([
            'anomaly'       => ['faa'],
            'level'         => ['level1'],
            'spatial_scope' => ['national'],
        ]);
        $this->assertCount(1, $results);
        $entry = $results[0];
        $this->assertSame('faa', $entry['anomaly_key']);
        $this->assertSame('level1', $entry['level_key']);
        $this->assertSame('national', $entry['spatial_scope']);
    }

    // -------------------------------------------------------------------------
    // catalogEntries() / catalogEntry() — requires DB
    // -------------------------------------------------------------------------

    public function testCatalogEntriesHaveSequentialIds(): void
    {
        $r = new GeoportalDatasetRegistry();
        $entries = $r->catalogEntries();
        foreach ($entries as $index => $entry) {
            $this->assertSame($index + 1, $entry['id'], "ID at index {$index} should be " . ($index + 1));
        }
    }

    public function testCatalogEntriesAreSortedByTitle(): void
    {
        $r = new GeoportalDatasetRegistry();
        $entries = $r->catalogEntries();
        $titles = array_column($entries, 'title');
        $sorted = $titles;
        sort($sorted);
        $this->assertSame($sorted, $titles);
    }

    public function testCatalogEntryReturnsEntryForValidId(): void
    {
        $r = new GeoportalDatasetRegistry();
        $entry = $r->catalogEntry(1);
        $this->assertIsArray($entry);
        $this->assertSame(1, $entry['id']);
    }

    public function testCatalogEntryReturnsNullForInvalidId(): void
    {
        $r = new GeoportalDatasetRegistry();
        $result = $r->catalogEntry(999999);
        $this->assertNull($result);
    }

    public function testCatalogEntriesResultIsCachedOnSecondCall(): void
    {
        $r = new GeoportalDatasetRegistry();
        $first = $r->catalogEntries();
        $second = $r->catalogEntries();
        // Same object reference (cache) — must be identical in content
        $this->assertSame($first, $second);
    }

    public function testCatalogEntriesNationalEntriesHaveNullProvinceId(): void
    {
        $r = new GeoportalDatasetRegistry();
        $nationals = array_filter($r->catalogEntries(), fn ($e) => $e['spatial_scope'] === 'national');
        foreach ($nationals as $entry) {
            $this->assertNull($entry['province_id'], "National entry should have null province_id");
        }
    }
}
