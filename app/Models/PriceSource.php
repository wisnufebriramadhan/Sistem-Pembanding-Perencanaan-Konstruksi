<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceSource extends Model
{
    protected $fillable = ['region_id', 'name', 'publisher', 'type', 'document_type', 'url', 'reference_year', 'status', 'last_checked_at', 'last_imported_at', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['last_checked_at' => 'datetime', 'last_imported_at' => 'datetime'];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
