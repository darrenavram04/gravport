<?php

namespace App\Models;

use CodeIgniter\Model;

class BaseDatasetModel extends Model
{
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'title',
        'slug',
        'country_code',
        'country_name',
        'spatial_scope',
        'is_downloadable',
        'is_viewable',
        'items_count',
        'backend_type',
        'data_schema',
        'data_table',
        'geom_column',
    ];

    public function getFiltered(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $builder = $this->builder();

        if (!empty($filters['q'])) {
            $builder->like('title', $filters['q'], 'both');
        }

        if (!empty($filters['downloadable'])) {
            $builder->where('is_downloadable', true);
        }

        if (!empty($filters['viewable'])) {
            $builder->where('is_viewable', true);
        }

        if (!empty($filters['spatial_scope']) && is_array($filters['spatial_scope'])) {
            $builder->whereIn('spatial_scope', $filters['spatial_scope']);
        }

        $total = $builder->countAllResults(false);

        $offset = ($page - 1) * $perPage;
        $builder->orderBy('title', 'ASC');
        $data = $builder->get($perPage, $offset)->getResultArray();

        return [$data, $total];
    }
}
