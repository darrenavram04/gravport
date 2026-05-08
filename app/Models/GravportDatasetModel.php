<?php

namespace App\Models;

class GravportDatasetModel extends BaseDatasetModel
{
    protected $DBGroup = 'gravport';
    protected $table   = 'datasets';   // table name only; schema is set in DB config
}
