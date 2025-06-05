<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'slug', 'teacher_id'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    public function schoolClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'course_school_classes');
    }
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}
