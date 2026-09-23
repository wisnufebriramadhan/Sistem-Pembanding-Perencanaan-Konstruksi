<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrectionFactor extends Model
{
    protected $fillable = ['code', 'name', 'multiplier', 'description', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['multiplier' => 'decimal:4', 'is_active' => 'boolean'];
    }
}
