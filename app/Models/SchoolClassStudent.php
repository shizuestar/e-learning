<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchoolClassStudent extends Model
{
    use HasFactory;

    protected $table = 'school_class_student'; // nama tabel pivot

    protected $fillable = [
        'school_class_id',
        'student_id',
    ];

    // Relasi ke SchoolClass
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    // Relasi ke Student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
