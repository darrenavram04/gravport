<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class GeoportalFeatureFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testWebMapBootstrapReturnsDatasetContract(): void
    {
        $result = $this->get('webmap/bootstrap');

        $result->assertStatus(200);

        $payload = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('faa_l1', $payload['defaultDataset']);
        $this->assertCount(4, $payload['datasets']);
        $this->assertTrue($payload['supports']['viewport_loading']);
        $this->assertTrue($payload['supports']['filtered_metadata_download']);
    }

    public function testWebMapLayerRejectsUnknownDataset(): void
    {
        $result = $this->withBodyFormat('json')->post('webmap/layer', [
            'dataset' => 'unknown',
        ]);

        $result->assertStatus(400);

        $payload = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Dataset tidak terdaftar.', $payload['error']);
    }

    public function testWebMapLayerRejectsOutOfRangeBounds(): void
    {
        $result = $this->withBodyFormat('json')->post('webmap/layer', [
            'dataset' => 'faa_l1',
            'bounds' => [
                'west' => 200,
                'south' => -95,
                'east' => 201,
                'north' => -94,
            ],
            'zoom' => 8,
        ]);

        $result->assertStatus(400);

        $payload = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Bounds berada di luar rentang koordinat geografis yang valid.', $payload['error']);
    }

    public function testMetadataRouteRedirectsGuestUsers(): void
    {
        $result = $this->get('metadata');

        $result->assertStatus(302);
        $this->assertStringEndsWith('/login', (string) $result->getRedirectUrl());
    }

    public function testMetadataStoreRejectsSubmissionWithoutAnyUpload(): void
    {
        $result = $this
            ->withSession([
                'logged_in' => true,
                'isLoggedIn' => true,
                'email' => 'tester@example.com',
                'role' => 'user',
            ])
            ->post('metadata', [
                'metadata_file_identifier' => 'meta-001',
                'jenis_data' => 'gravimetri',
                'provinsi' => 'Jawa Barat',
                'level_data' => 'level2',
                'bahasa' => 'ind',
                'character_set' => 'utf8',
                'hierarchy_level' => 'dataset',
                'metadata_date_stamp' => '2026-05-08',
                'individual_name' => 'Tester',
                'organisation_name' => 'Geoportal QA',
                'position_name' => 'Engineer',
                'contact_role' => 'pointOfContact',
                'voice' => '022123456',
                'facsimilie' => '022123457',
                'delivery_point' => 'Jl. Contoh 1',
                'city' => 'Bandung',
                'administrative_area' => 'Jawa Barat',
                'postal_code' => '40132',
                'country' => 'Indonesia',
                'electronic_mail_address' => 'tester@example.com',
            ]);

        $result->assertStatus(302);

        $errors = session()->getFlashdata('errors');

        $this->assertIsArray($errors);
        $this->assertContains('Unggah minimal satu file ZIP SHP, Excel/CSV, atau TIFF.', $errors);
    }

    public function testMetadataStoreRejectsInvalidEmailList(): void
    {
        $result = $this
            ->withSession([
                'logged_in' => true,
                'isLoggedIn' => true,
                'email' => 'tester@example.com',
                'role' => 'user',
            ])
            ->post('metadata', [
                'metadata_file_identifier' => 'meta-002',
                'jenis_data' => 'gravimetri',
                'provinsi' => 'Jawa Barat',
                'level_data' => 'level1',
                'bahasa' => 'ind',
                'character_set' => 'utf8',
                'hierarchy_level' => 'dataset',
                'metadata_date_stamp' => '2026-05-08',
                'individual_name' => 'Tester',
                'organisation_name' => 'Geoportal QA',
                'position_name' => 'Engineer',
                'contact_role' => 'pointOfContact',
                'voice' => '022123456',
                'facsimilie' => '022123457',
                'delivery_point' => 'Jl. Contoh 1',
                'city' => 'Bandung',
                'administrative_area' => 'Jawa Barat',
                'postal_code' => '40132',
                'country' => 'Indonesia',
                'electronic_mail_address' => 'tester@example.com, invalid-address',
            ]);

        $result->assertStatus(302);

        $errors = session()->getFlashdata('errors');

        $this->assertIsArray($errors);
        $this->assertSame(
            'Electronic Mail Address harus berupa satu email valid atau beberapa email valid yang dipisahkan dengan koma.',
            $errors['electronic_mail_address'] ?? null
        );
    }
}
