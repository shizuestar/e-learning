<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $guarded = [];
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(QuestionImage::class);
    }
}
