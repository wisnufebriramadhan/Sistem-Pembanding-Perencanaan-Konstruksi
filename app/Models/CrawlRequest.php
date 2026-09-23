<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrawlRequest extends Model
{
    protected $fillable = ['boq_import_id', 'region_id', 'sources', 'items', 'status', 'bank_hit_count', 'queued_item_count', 'completed_at', 'created_by'];

    protected function casts(): array
    {
        return ['sources' => 'array', 'items' => 'array', 'completed_at' => 'datetime'];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function candidates()
    {
        return $this->hasMany(PriceCandidate::class);
    }
}
