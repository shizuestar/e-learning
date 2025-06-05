<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = ['student_id', 'assignment_id', 'total_score', 'points', 'status', 'detail'];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function assignment() {
        return $this->belongsTo(Assignment::class);
    }
    public function answer()
    {
        return $this->hasOne(Answer::class, 'student_id', 'student_id')
                    ->whereColumn('assignment_id', 'assignment_id');
    }
}
