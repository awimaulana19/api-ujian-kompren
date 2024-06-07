@extends('Admin.Layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card p-3">
                <p>Jumlah Soal Mudah</p>
                <div class="d-flex">
                    <i class="menu-icon tf-icons bx bxs-book"></i>
                    <p class="fw-bold mb-0">{{ $soal_mudah }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card p-3">
                <p>Jumlah Soal Menengah</p>
                <div class="d-flex">
                    <i class="menu-icon tf-icons bx bxs-book"></i>
                    <p class="fw-bold mb-0">{{ $soal_menengah }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card p-3">
                <p>Jumlah Soal Sulit</p>
                <div class="d-flex">
                    <i class="menu-icon tf-icons bx bxs-book"></i>
                    <p class="fw-bold mb-0">{{ $soal_sulit }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
