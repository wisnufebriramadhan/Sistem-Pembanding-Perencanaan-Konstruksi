<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoqImport extends Model
{
    protected $fillable = ['project_id', 'region_id', 'original_filename', 'stored_path', 'status', 'project_metadata', 'items', 'item_count', 'matched_count', 'created_by'];

    protected function casts(): array
    {
        return ['project_metadata' => 'array', 'items' => 'array'];
    }
}
