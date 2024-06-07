<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matkul extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'user_id'];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function soal()
    {
        return $this->hasMany(Soal::class);
    }

    public function hasil()
    {
        return $this->hasMany(Hasil::class);
    }
}
