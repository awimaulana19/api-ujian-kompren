@extends('Admin.Layouts.app')

@section('title', 'Soal')

@section('content')
    <link href="//cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link href="//cdn.quilljs.com/1.3.6/quill.bubble.css" rel="stylesheet">

    <div class="col-12 mx-auto">
        <div class="card mb-4">
            <h5 class="card-header">Edit Soal</h5>
            <div class="card-body">
                @if ($errors->any())
                    @foreach ($errors->all() as $err)
                        <p class="alert alert-danger mt-2">{{ $err }}</p>
                    @endforeach
                @endif
                <form id="formedit" enctype="multipart/form-data" action="/admin/dosen/bank-soal/edit/{{ $id }}" method="POST">
                    @csrf
                    <input type="hidden" name="matkul_id" value="{{ $matkul->id }}">
                    <div class="mb-3">
                        <label class="form-label mb-2">Soal</label>
                        <input type="hidden" name="soal" id="soal" value="{{ $soal->soal }}">
                        <div id="snow">{!! $soal->soal !!}</div>
                    </div>
                    <div class="mb-3">
                        <label for="gambar_soal" class="form-label">Gambar</label>
                        <input type="file" id="gambar_soal" class="form-control" name="gambar_soal">
                        @if ($soal->gambar_soal)
                            <img src="{{ asset('storage/' . $soal->gambar_soal) }}" width="50%" alt="Gambar Soal"
                                class="img-thumbnail mt-2">
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="tingkat" class="form-label">Tingkatan Soal</label>
                        <select class="form-select" name="tingkat" id="tingkat">
                            <option value="Mudah" {{ $soal->tingkat == 'Mudah' ? 'selected' : '' }}>Mudah</option>
                            <option value="Menengah" {{ $soal->tingkat == 'Menengah' ? 'selected' : '' }}>Menengah</option>
                            <option value="Sulit" {{ $soal->tingkat == 'Sulit' ? 'selected' : '' }}>Sulit</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="m-0">Jawaban</h6>
                        <div class="d-flex gap-2">
                            <button type="button" id="bersihkan-jawaban-btn" class="btn btn-danger">Bersihkan</button>
                            <button type="button" id="add-jawaban-btn" class="btn btn-secondary">Tambah</button>
                        </div>
                    </div>
                    <div id="jawaban-container">
                        @foreach ($jawaban as $index => $jawab)
                            <div class="mb-3 jawaban-item">
                                <label class="form-label">Jawaban {{ chr(65 + $index) }} :</label>
                                <input class="form-control" type="text" name="jawaban[]" value="{{ $jawab->jawaban }}">
                                <div class="mt-3">
                                    <label class="form-label">Gambar</label>
                                    <input type="hidden" name="gambar_jawaban_lama[]" value="{{ $jawab->gambar_jawaban }}">
                                    <input type="file" class="form-control" name="gambar_jawaban[]">
                                    @if ($jawab->gambar_jawaban)
                                        <img src="{{ asset('storage/' . $jawab->gambar_jawaban) }}" width="50%"
                                            alt="Gambar Jawaban" class="img-thumbnail mt-2">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-3">
                        <label class="form-label me-3">Jawaban Benar :</label>
                        <div id="jawaban-benar-container">
                            @foreach ($jawaban as $index => $jawab)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="benar"
                                        id="radio{{ chr(65 + $index) }}" value="{{ chr(65 + $index) }}"
                                        {{ $jawab->is_correct ? 'checked' : '' }}>
                                    <label class="form-check-label"
                                        for="radio{{ chr(65 + $index) }}">{{ chr(65 + $index) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/dosen/bank-soal/{{ $matkul->id }}" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="//cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="//cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <script>
        var snow = new Quill('#snow', {
            theme: 'snow'
        });

        document.querySelector('#formedit').onsubmit = function() {
            var text = snow.root.innerHTML;
            document.querySelector('#soal').value = text;
        }
    </script>

    <script>
        document.getElementById('add-jawaban-btn').addEventListener('click', function() {
            let jawabanContainer = document.getElementById('jawaban-container');
            let jawabanBenarContainer = document.getElementById('jawaban-benar-container');

            let jawabanCount = jawabanContainer.querySelectorAll('.jawaban-item').length;
            let jawabanLabel = String.fromCharCode(65 + jawabanCount); // Convert to A, B, C, ...

            // Create new jawaban item
            let newJawabanItem = document.createElement('div');
            newJawabanItem.classList.add('mb-3', 'jawaban-item');
            newJawabanItem.innerHTML = `
                <label class="form-label">Jawaban ${jawabanLabel} :</label>
                <input class="form-control" type="text" name="jawaban[]">
                <div class="mt-3">
                    <label class="form-label">Gambar</label>
                    <input type="file" class="form-control" name="gambar_jawaban[]">
                </div>
            `;
            jawabanContainer.appendChild(newJawabanItem);

            // Create new radio button for jawaban benar
            let newRadio = document.createElement('div');
            newRadio.classList.add('form-check', 'form-check-inline');
            newRadio.innerHTML = `
                <input class="form-check-input" type="radio" name="benar" id="radio${jawabanLabel}" value="${jawabanLabel}">
                <label class="form-check-label" for="radio${jawabanLabel}">${jawabanLabel}</label>
            `;
            jawabanBenarContainer.appendChild(newRadio);
        });

        document.getElementById('bersihkan-jawaban-btn').addEventListener('click', function() {
            let jawabanContainer = document.getElementById('jawaban-container');
            let jawabanBenarContainer = document.getElementById('jawaban-benar-container');

            // Clear all but first two jawaban items
            while (jawabanContainer.children.length > 2) {
                jawabanContainer.removeChild(jawabanContainer.lastChild);
            }

            // Clear all but first two radio buttons
            while (jawabanBenarContainer.children.length > 2) {
                jawabanBenarContainer.removeChild(jawabanBenarContainer.lastChild);
            }
        });
    </script>
@endsection
