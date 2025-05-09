<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'nis',
        'address',
        'phone',
    ];
    public function schoolClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'school_class_students');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
