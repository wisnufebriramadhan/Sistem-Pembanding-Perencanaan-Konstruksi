<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['code', 'name', 'client', 'location', 'status', 'notes', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comparisons()
    {
        return $this->hasMany(Comparison::class);
    }
}
