<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hasil extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function soal(){
        return $this->belongsTo(Soal::class);
    }

    public function matkul(){
        return $this->belongsTo(Matkul::class);
    }
}
