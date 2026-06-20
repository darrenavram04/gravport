<?php

namespace App\Libraries;

use Config\Database;
use DOMDocument;
use RuntimeException;

class FilteredMetadataExporter
{
    public function export(array $dataset, array $filters = []): array
    {
        $db           = Database::connect();
        $provinceName = (string) ($dataset['province_name'] ?? '');
        $levelData    = ($dataset['level_key'] ?? '') === 'level1' ? 'Level 1' : 'Level 2';

        $row = null;
        if ($provinceName !== '') {
            $row = $db->query('
                SELECT
                    jenis_data,
                    provinsi,
                    level_data,
                    metadata_level,
                    source_path,
                    file_identifier,
                    parent_identifier,
                    hierarchy_level_name,
                    metadata_date,
                    language_code,
                    character_set,
                    title,
                    abstract,
                    organisation_name,
                    contact_role,
                    country,
                    raw_xml
                FROM geoportal.dataset_metadata_xml
                WHERE provinsi = ? AND level_data = ?
                ORDER BY jenis_data
                LIMIT 1
            ', [$provinceName, $levelData])->getRowArray();
        }

        if (!$row) {
            throw new RuntimeException('Metadata XML untuk dataset ini belum tersedia.');
        }

        $directory = WRITEPATH . 'exports' . DIRECTORY_SEPARATOR . 'metadata';
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder export metadata tidak dapat dibuat.');
        }

        $filename = $this->buildFilename($dataset, $filters);
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('filtered_metadata_export');
        $root->setAttribute('generated_at', date(DATE_ATOM));
        $doc->appendChild($root);

        $datasetNode = $doc->createElement('dataset');
        $root->appendChild($datasetNode);
        $this->appendNode($doc, $datasetNode, 'code', (string) ($dataset['dataset_code'] ?? $dataset['code'] ?? ''));
        $this->appendNode($doc, $datasetNode, 'title', (string) ($dataset['title'] ?? $dataset['label'] ?? ''));
        $this->appendNode($doc, $datasetNode, 'type', (string) ($dataset['type'] ?? ''));
        $this->appendNode($doc, $datasetNode, 'scope', (string) ($dataset['spatial_scope'] ?? ''));
        $this->appendNode($doc, $datasetNode, 'organization', (string) ($dataset['organization'] ?? ''));
        $this->appendNode($doc, $datasetNode, 'source_table', (string) (($dataset['data_schema'] ?? '') . '.' . ($dataset['data_table'] ?? '')));

        $filterNode = $doc->createElement('filters');
        $root->appendChild($filterNode);
        $this->appendNode($doc, $filterNode, 'province_id', $filters['province_id'] ?? $dataset['province_id'] ?? null);
        $this->appendNode($doc, $filterNode, 'province_name', $filters['province_name'] ?? $dataset['province_name'] ?? null);
        $this->appendNode($doc, $filterNode, 'geometry_type', $filters['geometry_type'] ?? null);
        $this->appendNode($doc, $filterNode, 'bbox', $this->boundsString($filters['bounds'] ?? null));

        $sourceNode = $doc->createElement('source_metadata');
        $root->appendChild($sourceNode);
        $this->appendNode($doc, $sourceNode, 'metadata_level', (string) ($row['metadata_level'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'source_path', (string) ($row['source_path'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'file_identifier', (string) ($row['file_identifier'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'parent_identifier', (string) ($row['parent_identifier'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'hierarchy_level_name', (string) ($row['hierarchy_level_name'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'metadata_date', (string) ($row['metadata_date'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'language_code', (string) ($row['language_code'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'character_set', (string) ($row['character_set'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'title', (string) ($row['title'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'abstract', (string) ($row['abstract'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'organisation_name', (string) ($row['organisation_name'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'contact_role', (string) ($row['contact_role'] ?? ''));
        $this->appendNode($doc, $sourceNode, 'country', (string) ($row['country'] ?? ''));

        $rawNode = $doc->createElement('raw_xml_cdata');
        $rawNode->appendChild($doc->createCDATASection((string) ($row['raw_xml'] ?? '')));
        $root->appendChild($rawNode);

        if ($doc->save($path) === false) {
            throw new RuntimeException('File metadata hasil filter tidak dapat ditulis.');
        }

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    private function appendNode(DOMDocument $doc, \DOMElement $parent, string $name, $value): void
    {
        $node = $doc->createElement($name);
        if ($value !== null && $value !== '') {
            $node->appendChild($doc->createTextNode((string) $value));
        }

        $parent->appendChild($node);
    }

    private function boundsString($bounds): ?string
    {
        if (!is_array($bounds)) {
            return null;
        }

        $required = ['west', 'south', 'east', 'north'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $bounds)) {
                return null;
            }
        }

        return implode(',', [
            $bounds['west'],
            $bounds['south'],
            $bounds['east'],
            $bounds['north'],
        ]);
    }

    private function buildFilename(array $dataset, array $filters): string
    {
        $parts = [
            (string) ($dataset['dataset_code'] ?? $dataset['code'] ?? 'dataset'),
            (string) ($dataset['spatial_scope'] ?? 'scope'),
        ];

        if (!empty($filters['province_id']) || !empty($dataset['province_id'])) {
            $parts[] = 'province_' . (int) ($filters['province_id'] ?? $dataset['province_id']);
        } elseif (!empty($filters['geometry_type'])) {
            $parts[] = strtolower((string) $filters['geometry_type']);
        } elseif (!empty($filters['bounds'])) {
            $parts[] = 'viewport';
        }

        $parts[] = date('Ymd_His');

        return implode('_', array_filter($parts)) . '.xml';
    }
}
