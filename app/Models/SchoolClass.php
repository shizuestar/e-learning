<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['teacher_id', 'name', 'slug', 'code'];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_school_classes');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'school_class_students');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}
