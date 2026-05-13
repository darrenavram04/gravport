<?php

declare(strict_types=1);

use App\Libraries\MetadataSubmissionService;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

final class MetadataSubmissionServiceWhiteBoxTest extends CIUnitTestCase
{
    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    public function testValidateSubmissionFilesAcceptsCompleteShapefileBundle(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile($this->buildZip([
            'bundle/test.shp' => 'shp',
            'bundle/test.shx' => 'shx',
            'bundle/test.dbf' => 'dbf',
        ]), 'zip');

        $errors = $service->validateSubmissionFiles([
            'shapefile_zip' => $this->mockUploadedFile($path, 'bundle.zip'),
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateSubmissionFilesRejectsIncompleteShapefileBundle(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile($this->buildZip([
            'bundle/test.shp' => 'shp',
            'bundle/test.dbf' => 'dbf',
        ]), 'zip');

        $errors = $service->validateSubmissionFiles([
            'shapefile_zip' => $this->mockUploadedFile($path, 'bundle.zip'),
        ]);

        $this->assertSame(
            'File ZIP SHP harus berisi minimal .shp, .shx, dan .dbf dengan nama layer yang sama.',
            $errors['shapefile_zip'] ?? null
        );
    }

    public function testValidateSubmissionFilesAcceptsMinimalXlsxWorkbook(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile($this->buildZip([
            '[Content_Types].xml' => '<Types/>',
            'xl/workbook.xml' => '<workbook/>',
        ]), 'xlsx');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'sheet.xlsx'),
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateSubmissionFilesRejectsMalformedCsv(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile("header-without-delimiter\nvalue-only", 'csv');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'broken.csv'),
        ]);

        $this->assertSame(
            'File CSV tidak valid atau tidak memiliki struktur tabel yang terbaca.',
            $errors['tabular_file'] ?? null
        );
    }

    public function testValidateSubmissionFilesRejectsInvalidRasterSignature(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile("NOT_A_TIFF", 'tif');

        $errors = $service->validateSubmissionFiles([
            'raster_file' => $this->mockUploadedFile($path, 'broken.tif'),
        ]);

        $this->assertSame(
            'File TIFF tidak valid atau header raster tidak dikenali.',
            $errors['raster_file'] ?? null
        );
    }

    // -------------------------------------------------------------------------
    // CSV format variants
    // -------------------------------------------------------------------------

    public function testValidateSubmissionFilesAcceptsCsvWithUtf8Bom(): void
    {
        $service = new MetadataSubmissionService();
        $bom = "\xEF\xBB\xBF";
        $path = $this->createTempFile($bom . "lintang,bujur,faa\n-6.9,107.6,125.5", 'csv');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'bom.csv'),
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateSubmissionFilesAcceptsCsvWithSemicolonDelimiter(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile("lintang;bujur;faa\n-6.9;107.6;125.5", 'csv');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'semicolon.csv'),
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateSubmissionFilesAcceptsCsvWithTabDelimiter(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile("lintang\tbujur\tfaa\n-6.9\t107.6\t125.5", 'csv');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'tab.csv'),
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateSubmissionFilesRejectsEmptyCsv(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile('', 'csv');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'empty.csv'),
        ]);

        $this->assertSame(
            'File CSV tidak valid atau tidak memiliki struktur tabel yang terbaca.',
            $errors['tabular_file'] ?? null
        );
    }

    // -------------------------------------------------------------------------
    // XLSX structure variants
    // -------------------------------------------------------------------------

    public function testValidateSubmissionFilesRejectsXlsxMissingWorkbookXml(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile($this->buildZip([
            '[Content_Types].xml' => '<Types/>',
            // xl/workbook.xml is intentionally absent
            'xl/styles.xml' => '<styles/>',
        ]), 'xlsx');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'no_workbook.xlsx'),
        ]);

        $this->assertSame(
            'File XLSX tidak valid atau struktur workbook tidak lengkap.',
            $errors['tabular_file'] ?? null
        );
    }

    public function testValidateSubmissionFilesRejectsXlsxMissingContentTypes(): void
    {
        $service = new MetadataSubmissionService();
        $path = $this->createTempFile($this->buildZip([
            // [Content_Types].xml is intentionally absent
            'xl/workbook.xml' => '<workbook/>',
        ]), 'xlsx');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'no_content_types.xlsx'),
        ]);

        $this->assertSame(
            'File XLSX tidak valid atau struktur workbook tidak lengkap.',
            $errors['tabular_file'] ?? null
        );
    }

    // -------------------------------------------------------------------------
    // XLS (OLE Compound Document) signature
    // -------------------------------------------------------------------------

    public function testValidateSubmissionFilesAcceptsXlsWithValidOleSignature(): void
    {
        $service = new MetadataSubmissionService();
        // OLE compound document header: D0 CF 11 E0 A1 B1 1A E1
        $oleHeader = hex2bin('D0CF11E0A1B11AE1') . str_repeat("\x00", 512);
        $path = $this->createTempFile($oleHeader, 'xls');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'valid.xls'),
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateSubmissionFilesRejectsXlsWithWrongSignature(): void
    {
        $service = new MetadataSubmissionService();
        // Wrong header — just arbitrary bytes that do not match OLE signature
        $path = $this->createTempFile(str_repeat("\x00", 512), 'xls');

        $errors = $service->validateSubmissionFiles([
            'tabular_file' => $this->mockUploadedFile($path, 'invalid.xls'),
        ]);

        $this->assertSame(
            'File XLS tidak valid atau bukan workbook Excel biner yang dikenali.',
            $errors['tabular_file'] ?? null
        );
    }

    // -------------------------------------------------------------------------
    // ZIP content-type rejection (non-ZIP file sent as ZIP)
    // -------------------------------------------------------------------------

    public function testValidateSubmissionFilesRejectsNonZipFileAsSHP(): void
    {
        $service = new MetadataSubmissionService();
        // Plain text does not start with PK magic bytes
        $path = $this->createTempFile("This is not a ZIP file\n", 'zip');

        $errors = $service->validateSubmissionFiles([
            'shapefile_zip' => $this->mockUploadedFile($path, 'fake.zip'),
        ]);

        $this->assertSame(
            'File ZIP SHP bukan arsip ZIP yang valid.',
            $errors['shapefile_zip'] ?? null
        );
    }

    // -------------------------------------------------------------------------
    // ZIP with valid PK signature but no EOCD → zipEntryNames throws
    // -------------------------------------------------------------------------

    public function testValidateSubmissionFilesRejectsZipWithMissingEocd(): void
    {
        $service = new MetadataSubmissionService();
        // Starts with PK (valid signature) but has no End-of-Central-Directory record
        $path = $this->createTempFile("PK\x03\x04" . str_repeat("\x00", 50), 'zip');

        $errors = $service->validateSubmissionFiles([
            'shapefile_zip' => $this->mockUploadedFile($path, 'no_eocd.zip'),
        ]);

        $this->assertSame(
            'File ZIP SHP tidak dapat dibaca sebagai arsip ZIP yang valid.',
            $errors['shapefile_zip'] ?? null
        );
    }

    // -------------------------------------------------------------------------
    // TIFF endianness variants
    // -------------------------------------------------------------------------

    public function testValidateSubmissionFilesAcceptsBigEndianTiff(): void
    {
        $service = new MetadataSubmissionService();
        // Big-endian TIFF: MM\x00*
        $path = $this->createTempFile("MM\x00*" . str_repeat("\x00", 64), 'tif');

        $errors = $service->validateSubmissionFiles([
            'raster_file' => $this->mockUploadedFile($path, 'bigendian.tif'),
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateSubmissionFilesAcceptsBigTiffLittleEndian(): void
    {
        $service = new MetadataSubmissionService();
        // BigTIFF little-endian: II+\x00
        $path = $this->createTempFile("II+\x00" . str_repeat("\x00", 64), 'tif');

        $errors = $service->validateSubmissionFiles([
            'raster_file' => $this->mockUploadedFile($path, 'bigtiff_le.tif'),
        ]);

        $this->assertSame([], $errors);
    }

    private function createTempFile(string $contents, string $extension): string
    {
        $base = tempnam(sys_get_temp_dir(), 'geoportal_');
        if ($base === false) {
            $this->fail('Gagal membuat file sementara untuk test.');
        }

        $path = $base . '.' . $extension;

        if (!rename($base, $path)) {
            $path = $base;
        }

        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function mockUploadedFile(string $path, string $clientName): UploadedFile
    {
        $extension = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));

        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isValid', 'getError', 'getErrorString', 'getTempName', 'getClientExtension', 'getExtension'])
            ->getMock();

        $file->method('isValid')->willReturn(true);
        $file->method('getError')->willReturn(UPLOAD_ERR_OK);
        $file->method('getErrorString')->willReturn('simulated upload error');
        $file->method('getTempName')->willReturn($path);
        $file->method('getClientExtension')->willReturn($extension);
        $file->method('getExtension')->willReturn($extension);

        return $file;
    }

    /**
     * @param array<string, string> $entries
     */
    private function buildZip(array $entries): string
    {
        $locals = '';
        $central = '';
        $offset = 0;

        foreach ($entries as $name => $content) {
            $name = str_replace('\\', '/', $name);
            $crc = hexdec(hash('crc32b', $content));
            $size = strlen($content);
            $nameLength = strlen($name);

            $local = pack(
                'VvvvvvVVVvv',
                0x04034B50,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $nameLength,
                0
            ) . $name . $content;

            $locals .= $local;

            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50,
                20,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $name;

            $offset += strlen($local);
        }

        $centralOffset = strlen($locals);
        $centralSize = strlen($central);
        $entriesTotal = count($entries);

        $eocd = pack(
            'VvvvvVVv',
            0x06054B50,
            0,
            0,
            $entriesTotal,
            $entriesTotal,
            $centralSize,
            $centralOffset,
            0
        );

        return $locals . $central . $eocd;
    }
}
