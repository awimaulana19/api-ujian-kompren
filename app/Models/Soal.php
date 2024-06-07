<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Soal extends Model
{
    use HasFactory;

    protected $fillable = ['soal', 'matkul_id'];

    public function jawaban()
    {
        return $this->hasMany(Jawaban::class);
    }

    public function matkul(){
        return $this->belongsTo(Matkul::class, 'matkul_id');
    }

    public function hasil()
    {
        return $this->hasMany(Hasil::class);
    }
}
