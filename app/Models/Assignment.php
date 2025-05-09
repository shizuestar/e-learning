<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $guarded = [];
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
    // Fungsi untuk set slug otomatis
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($assignment) {
            $assignment->slug = Str::slug($assignment->title) . '-' . uniqid();
        });
    }
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function results() {
        return $this->hasMany(Result::class);
    }
}
