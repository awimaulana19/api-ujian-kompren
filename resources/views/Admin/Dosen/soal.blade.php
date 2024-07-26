@extends('Admin.Layouts.app')

@section('title', 'Soal')

@section('content')
    <link href="//cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link href="//cdn.quilljs.com/1.3.6/quill.bubble.css" rel="stylesheet">
    <script src="//cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="//cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <style>
        tr .soal-td p:first-child {
            display: inline;
        }
    </style>
    <div class="row">
        <div class="col-lg-12 col-md-12 mb-4">
            <div class="card p-4">
                <div class="d-flex">
                    <h5>Soal {{ $matkul->nama }}</h5>
                </div>
                <div class="table-responsive mt-4">
                    <table class="table table-hover" id="table1">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">Soal</th>
                                <th class="text-center">Tingkatan Soal</th>
                                <th class="text-center">Jawaban</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($matkul->soal as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="d-flex align-items-center justify-content-center text-center soal-td">
                                        {!! $item->soal !!}</td>
                                    <td class="text-center">{{ $item->tingkat }}</td>
                                    <td class="text-center">
                                        <a href="/admin/dosen/bank-soal/edit/{{ $item->id }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
