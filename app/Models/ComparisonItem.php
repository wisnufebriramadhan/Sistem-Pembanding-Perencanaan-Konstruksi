<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComparisonItem extends Model
{
    protected $fillable = ['comparison_id', 'reference_price_id', 'description', 'unit', 'quantity', 'reference_unit_price', 'correction_multiplier', 'estimated_unit_price', 'notes'];
}
