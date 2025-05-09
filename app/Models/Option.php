<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $fillable = ['question_id', 'option_text', 'correct_option'];

    protected $casts = [
        'option_text' => 'array', // Mengonversi JSON ke array otomatis
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
