<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferencePrice extends Model
{
    protected $fillable = ['region_id', 'item_code', 'name', 'category', 'unit', 'unit_price', 'source_name', 'source_type', 'priority', 'source_year', 'source_url', 'region', 'effective_date', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'effective_date' => 'date', 'is_active' => 'boolean'];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
