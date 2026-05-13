<?php

declare(strict_types=1);

use App\Libraries\DatasetImportService;
use CodeIgniter\Test\CIUnitTestCase;

final class DatasetImportServiceWhiteBoxTest extends CIUnitTestCase
{
    private function invokePrivate(object $target, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }

    public function testBuildCsvColumnMapRecognizesExpectedAliases(): void
    {
        $service = new DatasetImportService();

        $map = $this->invokePrivate($service, 'buildCsvColumnMap', [[
            ' Lintang ',
            'Bujur',
            'Tinggi Ort.',
            'FAA',
        ], 'faa', 'sample.csv']);

        $this->assertSame([
            'latitude' => 0,
            'longitude' => 1,
            'orthometric_height' => 2,
            'anomaly_value' => 3,
        ], $map);
    }

    public function testBuildCsvColumnMapThrowsWhenRequiredColumnIsMissing(): void
    {
        $service = new DatasetImportService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kolom anomaly_value tidak ditemukan pada broken.csv.');

        $this->invokePrivate($service, 'buildCsvColumnMap', [[
            'Lintang',
            'Bujur',
            'Tinggi Ortometrik',
        ], 'faa', 'broken.csv']);
    }

    public function testMapCsvRowParsesNumbersAndNullableHeight(): void
    {
        $service = new DatasetImportService();

        $row = $this->invokePrivate($service, 'mapCsvRow', [[
            '-6.9147',
            '107.6098',
            '',
            '125.5',
        ], [
            'latitude' => 0,
            'longitude' => 1,
            'orthometric_height' => 2,
            'anomaly_value' => 3,
        ], 'sample.csv', 'faa']);

        $this->assertSame(-6.9147, $row['latitude']);
        $this->assertSame(107.6098, $row['longitude']);
        $this->assertNull($row['orthometric_height']);
        $this->assertSame(125.5, $row['anomaly_value']);
    }

    public function testToFloatRejectsInvalidNumericTokens(): void
    {
        $service = new DatasetImportService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nilai FAA tidak valid pada sample.csv: NaN');

        $this->invokePrivate($service, 'toFloat', ['NaN', 'FAA', 'sample.csv']);
    }

    public function testToNullableFloatReturnsNullForBlankAndInfinity(): void
    {
        $service = new DatasetImportService();

        $this->assertNull($this->invokePrivate($service, 'toNullableFloat', ['']));
        $this->assertNull($this->invokePrivate($service, 'toNullableFloat', ['Infinity']));
        $this->assertSame(12.75, $this->invokePrivate($service, 'toNullableFloat', ['12.75']));
    }

    public function testDetectSurveyModeUsesFilenameKeywords(): void
    {
        $service = new DatasetImportService();

        $this->assertSame('airborne', $this->invokePrivate($service, 'detectSurveyMode', ['FAA_Airborne_01.csv']));
        $this->assertSame('terestris', $this->invokePrivate($service, 'detectSurveyMode', ['faa_terestris_jabar.csv']));
        $this->assertSame('unknown', $this->invokePrivate($service, 'detectSurveyMode', ['faa_misc.csv']));
    }

    // -------------------------------------------------------------------------
    // rowIsEmpty
    // -------------------------------------------------------------------------

    public function testRowIsEmptyReturnsTrueForAllEmptyCells(): void
    {
        $service = new DatasetImportService();
        $this->assertTrue($this->invokePrivate($service, 'rowIsEmpty', [['', '', '']]));
    }

    public function testRowIsEmptyReturnsTrueForWhitespaceOnlyCells(): void
    {
        $service = new DatasetImportService();
        $this->assertTrue($this->invokePrivate($service, 'rowIsEmpty', [['   ', "\t", "\n"]]));
    }

    public function testRowIsEmptyReturnsFalseWhenOneCellHasContent(): void
    {
        $service = new DatasetImportService();
        $this->assertFalse($this->invokePrivate($service, 'rowIsEmpty', [['', '0', '']]));
    }

    public function testRowIsEmptyReturnsFalseForSingleNonEmptyRow(): void
    {
        $service = new DatasetImportService();
        $this->assertFalse($this->invokePrivate($service, 'rowIsEmpty', [['-6.9147']]));
    }

    // -------------------------------------------------------------------------
    // normalizeColumnName
    // -------------------------------------------------------------------------

    public function testNormalizeColumnNameStripsSpecialCharsAndLowercases(): void
    {
        $service = new DatasetImportService();
        $this->assertSame('tinggiort', $this->invokePrivate($service, 'normalizeColumnName', ['Tinggi Ort.']));
        $this->assertSame('tinggiortometrik', $this->invokePrivate($service, 'normalizeColumnName', ['Tinggi Ortometrik']));
        $this->assertSame('lintang', $this->invokePrivate($service, 'normalizeColumnName', [' Lintang ']));
        $this->assertSame('bujur', $this->invokePrivate($service, 'normalizeColumnName', ['BUJUR']));
        $this->assertSame('faa', $this->invokePrivate($service, 'normalizeColumnName', ['FAA']));
    }

    public function testNormalizeColumnNameHandlesEmptyString(): void
    {
        $service = new DatasetImportService();
        $this->assertSame('', $this->invokePrivate($service, 'normalizeColumnName', ['']));
    }

    // -------------------------------------------------------------------------
    // isInvalidNumericToken — all sentinel values (case-insensitive)
    // -------------------------------------------------------------------------

    public function testIsInvalidNumericTokenReturnsTrueForNaN(): void
    {
        $service = new DatasetImportService();
        $this->assertTrue($this->invokePrivate($service, 'isInvalidNumericToken', ['NaN']));
        $this->assertTrue($this->invokePrivate($service, 'isInvalidNumericToken', ['nan']));
        $this->assertTrue($this->invokePrivate($service, 'isInvalidNumericToken', ['NAN']));
    }

    public function testIsInvalidNumericTokenReturnsTrueForInfinity(): void
    {
        $service = new DatasetImportService();
        $this->assertTrue($this->invokePrivate($service, 'isInvalidNumericToken', ['Inf']));
        $this->assertTrue($this->invokePrivate($service, 'isInvalidNumericToken', ['-inf']));
        $this->assertTrue($this->invokePrivate($service, 'isInvalidNumericToken', ['Infinity']));
        $this->assertTrue($this->invokePrivate($service, 'isInvalidNumericToken', ['-Infinity']));
        $this->assertTrue($this->invokePrivate($service, 'isInvalidNumericToken', ['-infinity']));
    }

    public function testIsInvalidNumericTokenReturnsFalseForValidNumbers(): void
    {
        $service = new DatasetImportService();
        $this->assertFalse($this->invokePrivate($service, 'isInvalidNumericToken', ['0']));
        $this->assertFalse($this->invokePrivate($service, 'isInvalidNumericToken', ['-6.9147']));
        $this->assertFalse($this->invokePrivate($service, 'isInvalidNumericToken', ['125.5']));
        $this->assertFalse($this->invokePrivate($service, 'isInvalidNumericToken', ['-0.001']));
    }

    // -------------------------------------------------------------------------
    // packagePath — path validation
    // -------------------------------------------------------------------------

    public function testPackagePathThrowsForEmptyPackageName(): void
    {
        $service = new DatasetImportService();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nama paket import tidak boleh kosong.');
        $this->invokePrivate($service, 'packagePath', ['']);
    }

    public function testPackagePathThrowsForWhitespaceOnlyName(): void
    {
        $service = new DatasetImportService();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nama paket import tidak boleh kosong.');
        $this->invokePrivate($service, 'packagePath', ['   ']);
    }

    public function testPackagePathThrowsForNonExistentDirectory(): void
    {
        $service = new DatasetImportService();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak ditemukan');
        $this->invokePrivate($service, 'packagePath', ['nonexistent_package_xyzzy_9999']);
    }

    public function testPackagePathThrowsForDirectoryTraversalAttempt(): void
    {
        $service = new DatasetImportService();
        $this->expectException(RuntimeException::class);
        // Path traversal using ../ should either be "not found" or "outside directory"
        $this->invokePrivate($service, 'packagePath', ['../config']);
    }

    // -------------------------------------------------------------------------
    // parseMetadataXml
    // -------------------------------------------------------------------------

    public function testParseMetadataXmlExtractsUniqueEmailsAndNormalizesWhitespace(): void
    {
        $service = new DatasetImportService();
        $path = WRITEPATH . 'tests_metadata_whitebox.xml';

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<gmd:MD_Metadata xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco">
  <gmd:fileIdentifier><gco:CharacterString> id-001 </gco:CharacterString></gmd:fileIdentifier>
  <gmd:parentIdentifier><gco:CharacterString> parent-01 </gco:CharacterString></gmd:parentIdentifier>
  <gmd:hierarchyLevelName><gco:CharacterString> level 2 </gco:CharacterString></gmd:hierarchyLevelName>
  <gmd:dateStamp><gco:Date>2026-05-08</gco:Date></gmd:dateStamp>
  <gmd:language><gmd:LanguageCode codeListValue="ind"/></gmd:language>
  <gmd:characterSet><gmd:MD_CharacterSetCode codeListValue="utf8"/></gmd:characterSet>
  <gmd:contact>
    <gmd:CI_ResponsibleParty>
      <gmd:organisationName><gco:CharacterString> Geoportal   Lab </gco:CharacterString></gmd:organisationName>
      <gmd:contactInfo>
        <gmd:CI_Contact>
          <gmd:address>
            <gmd:CI_Address>
              <gmd:electronicMailAddress><gco:CharacterString>first@example.com</gco:CharacterString></gmd:electronicMailAddress>
              <gmd:electronicMailAddress><gco:CharacterString> first@example.com </gco:CharacterString></gmd:electronicMailAddress>
              <gmd:electronicMailAddress><gco:CharacterString>second@example.com</gco:CharacterString></gmd:electronicMailAddress>
            </gmd:CI_Address>
          </gmd:address>
        </gmd:CI_Contact>
      </gmd:contactInfo>
    </gmd:CI_ResponsibleParty>
  </gmd:contact>
  <gmd:identificationInfo>
    <gmd:MD_DataIdentification>
      <gmd:citation>
        <gmd:CI_Citation>
          <gmd:title><gco:CharacterString> Raster    Uji </gco:CharacterString></gmd:title>
        </gmd:CI_Citation>
      </gmd:citation>
      <gmd:abstract><gco:CharacterString> Metadata   untuk   pengujian </gco:CharacterString></gmd:abstract>
      <gmd:pointOfContact>
        <gmd:CI_ResponsibleParty>
          <gmd:role><gmd:CI_RoleCode codeListValue="pointOfContact"/></gmd:role>
        </gmd:CI_ResponsibleParty>
      </gmd:pointOfContact>
    </gmd:MD_DataIdentification>
  </gmd:identificationInfo>
</gmd:MD_Metadata>
XML;

        file_put_contents($path, $xml);

        try {
            $parsed = $this->invokePrivate($service, 'parseMetadataXml', [$path]);
        } finally {
            @unlink($path);
        }

        $this->assertSame('id-001', $parsed['file_identifier']);
        $this->assertSame('parent-01', $parsed['parent_identifier']);
        $this->assertSame('Raster Uji', $parsed['title']);
        $this->assertSame('Metadata untuk pengujian', $parsed['abstract']);
        $this->assertSame('Geoportal Lab', $parsed['organisation_name']);
        $this->assertSame(['first@example.com', 'second@example.com'], $parsed['emails']);
        $this->assertSame('pointOfContact', $parsed['contact_role']);
    }
}
