<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comparison extends Model
{
    protected $fillable = ['project_id', 'name', 'status', 'total_reference_value', 'total_estimated_value', 'created_by'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(ComparisonItem::class);
    }
}
