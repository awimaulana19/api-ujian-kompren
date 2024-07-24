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
                <div class="table-responsive text-nowrap mt-4">
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
                                    <td class="text-center soal-td">{!! $item->soal !!}</td>
                                    <td class="text-center">{{ $item->tingkat }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#exampleModal{{ $item->id }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                    <div class="modal fade" id="exampleModal{{ $item->id }}" tabindex="-1"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Verifikasi Akun
                                                    </h1>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <div id="jawaban-container">
                                                            @foreach ($item->jawaban as $index => $jawab)
                                                                <div class="mb-3 jawaban-item">
                                                                    <label class="form-label">Jawaban {{ chr(65 + $index) }}
                                                                        :</label>
                                                                    <textarea disabled class="form-control" cols="15" rows="2">{{ $jawab->jawaban }}</textarea>
                                                                    <div class="mt-3">
                                                                        <label class="form-label">Gambar</label>
                                                                        <input type="hidden" name="gambar_jawaban_lama[]"
                                                                            value="{{ $jawab->gambar_jawaban }}">
                                                                        @if ($jawab->gambar_jawaban)
                                                                            <img src="{{ asset('storage/' . $jawab->gambar_jawaban) }}"
                                                                                width="50%" alt="Gambar Jawaban"
                                                                                class="img-thumbnail mt-2">
                                                                        @else
                                                                            <br>
                                                                            Tidak Ada
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label me-3">Jawaban Benar :</label>
                                                            <div id="jawaban-benar-container">
                                                                @foreach ($item->jawaban as $index => $jawab)
                                                                    <div class="form-check form-check-inline">
                                                                        <input disabled class="form-check-input" type="radio"
                                                                            name="benar{{ $item->id }}"
                                                                            id="radio{{ chr(65 + $index) }}"
                                                                            value="{{ chr(65 + $index) }}"
                                                                            {{ $jawab->is_correct ? 'checked' : '' }}>
                                                                        <label class="form-check-label"
                                                                            for="radio{{ chr(65 + $index) }}">{{ chr(65 + $index) }}</label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
