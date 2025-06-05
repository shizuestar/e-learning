<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialFile extends Model
{
    protected $fillable = [
        'material_id',
        'file_path',
        'file_type',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
