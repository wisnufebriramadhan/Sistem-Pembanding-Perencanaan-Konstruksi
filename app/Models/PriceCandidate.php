<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceCandidate extends Model
{
    protected $fillable = [
        'price_source_id', 'external_key', 'name', 'unit', 'unit_price', 'raw_payload', 'status', 'received_at',
        'reviewed_by', 'reviewed_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return ['raw_payload' => 'array', 'received_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function source()
    {
        return $this->belongsTo(PriceSource::class, 'price_source_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
