<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Matkul extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'user_id', 'matakuliah_id'];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function matakuliah(){
        return $this->belongsTo(Matakuliah::class, 'matakuliah_id');
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
