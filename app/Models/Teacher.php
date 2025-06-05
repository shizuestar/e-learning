<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;
    // protected $fillable = [
    //     'user_id',
    //     'nip',
    //     'address',
    //     'phone',
    // ];
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
    public function schoolClasses()
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}
