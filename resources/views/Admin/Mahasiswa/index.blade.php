@extends('Admin.Layouts.app')

@section('title', 'Data Mahasiswa')

@section('content')
    <div class="row">
        <div class="col-lg-12 col-md-12 mb-4">
            <div class="card p-4">
                <div class="d-flex">
                    <h5>Data Mahasiswa</h5>
                </div>
                <div class="table-responsive text-nowrap mt-4">
                    <table class="table table-hover" id="table1">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">Nama</th>
                                <th class="text-center">Username/Nim</th>
                                <th class="text-center">Sk Kompren</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($user as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="text-center">{{ $item->nama }}</td>
                                    <td class="text-center"><span>{{ $item->username }}</span></td>
                                    <td class="text-center">
                                        <a class="text-decoration-underline"
                                            href="{{ asset('storage/skKompren/' . $item->sk_kompren) }}"
                                            target="_blank">Lihat
                                            SK Kompren
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#exampleModal{{ $item->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <a href="/admin/mahasiswa/delete/{{ $item->id }}" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus item ini?')">
                                            <i class="bx bx-trash"></i>
                                        </a>
                                    </td>
                                    <!-- Modal -->
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
                                                    <form action="/admin/mahasiswa/update/{{ $item->id }}"
                                                        method="POST">
                                                        @csrf
                                                        <div class="mb-3">
                                                            <label for="" class="form-label">Username</label>
                                                            <input type="text" value="{{ $item->username }}" disabled
                                                                class="form-control" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="is_verification" class="form-label">Status
                                                                Akun</label>
                                                            <select name="is_verification"
                                                                id="is_verification{{ $item->id }}" class="form-select">
                                                                <option value="0" data-tolak="false"
                                                                    {{ $item->is_verification == '0' ? 'selected' : '' }}>
                                                                    Belum Verifikasi</option>
                                                                <option value="1" data-tolak="false"
                                                                    {{ $item->is_verification == '1' ? 'selected' : '' }}>
                                                                    Verifikasi</option>
                                                                <option class="tolak" value="0" data-tolak="true"
                                                                    {{ $item->is_verification == '0' && $item->tolak ? 'selected' : '' }}>
                                                                    Tolak</option>
                                                            </select>
                                                        </div>
                                                        <div id="tolakTextareaContainer{{ $item->id }}"
                                                            style="display: none;" class="mb-3">
                                                            <label for="tolak" class="form-label">Alasan
                                                                Penolakan</label>
                                                            <textarea id="tolak{{ $item->id }}" required name="tolak" class="form-control" rows="3"></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            @php
                                                                $penguji = json_decode($item->penguji);
                                                            @endphp
                                                            <label for="penguji_1{{ $item->id }}"
                                                                class="form-label">Penguji 1</label>
                                                            <select name="penguji_1" id="penguji_1{{ $item->id }}"
                                                                class="form-select"
                                                                onchange="updateMatkul1{{ $item->id }}()">
                                                                <option value="">Pilih Dosen</option>
                                                                @foreach ($dosen as $row)
                                                                    <option value="{{ $row->id }}"
                                                                        {{ $row->id == $penguji->penguji_1->user_id ? 'selected' : '' }}>
                                                                        {{ $row->nama }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <select name="matkul_1" id="matkul_1{{ $item->id }}"
                                                                class="form-select mt-2">
                                                                <option value="">Pilih Matkul</option>
                                                            </select>
                                                            <input type="hidden" id="value_matkul_1{{ $item->id }}"
                                                                value="{{ $penguji->penguji_1->matkul_id }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="penguji_2{{ $item->id }}"
                                                                class="form-label">Penguji 2</label>
                                                            <select name="penguji_2" id="penguji_2{{ $item->id }}"
                                                                class="form-select"
                                                                onchange="updateMatkul2{{ $item->id }}()">
                                                                <option value="">Pilih Dosen</option>
                                                                @foreach ($dosen as $row)
                                                                    <option value="{{ $row->id }}"
                                                                        {{ $row->id == $penguji->penguji_2->user_id ? 'selected' : '' }}>
                                                                        {{ $row->nama }}</option>
                                                                @endforeach
                                                            </select>
                                                            <select name="matkul_2" id="matkul_2{{ $item->id }}"
                                                                class="form-select mt-2">
                                                                <option value="">Pilih Matkul</option>
                                                            </select>
                                                            <input type="hidden" id="value_matkul_2{{ $item->id }}"
                                                                value="{{ $penguji->penguji_2->matkul_id }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="penguji_3{{ $item->id }}"
                                                                class="form-label">Penguji 3</label>
                                                            <select name="penguji_3" id="penguji_3{{ $item->id }}"
                                                                class="form-select"
                                                                onchange="updateMatkul3{{ $item->id }}()">
                                                                <option value="">Pilih Dosen</option>
                                                                @foreach ($dosen as $row)
                                                                    <option value="{{ $row->id }}"
                                                                        {{ $row->id == $penguji->penguji_3->user_id ? 'selected' : '' }}>
                                                                        {{ $row->nama }}</option>
                                                                @endforeach
                                                            </select>
                                                            <select name="matkul_3" id="matkul_3{{ $item->id }}"
                                                                class="form-select mt-2">
                                                                <option value="">Pilih Matkul</option>
                                                            </select>
                                                            <input type="hidden" id="value_matkul_3{{ $item->id }}"
                                                                value="{{ $penguji->penguji_3->matkul_id }}">
                                                        </div>
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Close</button>
                                                            <button type="submit"
                                                                class="btn btn-primary">Verifikasi</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </tr>

                                <script>
                                    var isVerificationSelect = document.getElementById('is_verification{{ $item->id }}');
                                    var tolakTextareaContainer = document.getElementById('tolakTextareaContainer{{ $item->id }}');
                                    var tolakTextarea = document.getElementById('tolak{{ $item->id }}');

                                    // Mendapatkan nilai awal dari select
                                    var selectedValue = isVerificationSelect.value;
                                    var dataTolak = isVerificationSelect.options[isVerificationSelect.selectedIndex].getAttribute('data-tolak');

                                    // Memeriksa apakah textarea harus ditampilkan pada nilai awal
                                    if (selectedValue === '0' && dataTolak === 'true') {
                                        tolakTextareaContainer.style.display = 'block';
                                        tolakTextarea.value = '{{ $item->tolak }}';
                                    } else {
                                        tolakTextareaContainer.style.display = 'none';
                                        tolakTextarea.setAttribute('disabled', 'disabled');
                                        tolakTextarea.value = '';
                                    }

                                    document.getElementById('is_verification{{ $item->id }}').addEventListener('change', function() {
                                        var selectedValue = this.value;
                                        var dataTolak = this.options[this.selectedIndex].getAttribute('data-tolak');
                                        var tolakTextareaContainer = document.getElementById('tolakTextareaContainer{{ $item->id }}');
                                        var tolakTextarea = document.getElementById('tolak{{ $item->id }}');

                                        if (selectedValue == '0' && dataTolak == 'true') {
                                            tolakTextareaContainer.style.display = 'block';
                                            tolakTextarea.removeAttribute('disabled');
                                        } else {
                                            tolakTextareaContainer.style.display = 'none';
                                            tolakTextarea.setAttribute('disabled', 'disabled');
                                            tolakTextarea.value = '';
                                        }
                                    });
                                </script>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @foreach ($user as $item)
        <script>
            document.addEventListener('DOMContentLoaded', async function() {
                await updateMatkul1{{ $item->id }}();
            });

            async function updateMatkul1{{ $item->id }}() {
                const penguji = document.getElementById('penguji_1{{ $item->id }}').value;
                const value_matkul = document.getElementById('value_matkul_1{{ $item->id }}').value;

                document.getElementById('matkul_1{{ $item->id }}').innerHTML =
                    '<option value="">Pilih Matkul</option>';
                document.getElementById('matkul_1{{ $item->id }}').disabled = true;

                try {
                    const response = await fetch(`/matkul-list?penguji=${penguji}`);
                    const data = await response.json();

                    data.forEach(matkul => {
                        const option = document.createElement('option');
                        if (value_matkul == matkul.id) {
                            option.selected = true;
                        }
                        option.value = matkul.id;
                        option.text = matkul.nama;
                        document.getElementById('matkul_1{{ $item->id }}').appendChild(option);
                    });

                    document.getElementById('matkul_1{{ $item->id }}').disabled = false;
                } catch (error) {
                    console.error(error);
                }
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', async function() {
                await updateMatkul2{{ $item->id }}();
            });

            async function updateMatkul2{{ $item->id }}() {
                const penguji = document.getElementById('penguji_2{{ $item->id }}').value;
                const value_matkul = document.getElementById('value_matkul_2{{ $item->id }}').value;

                document.getElementById('matkul_2{{ $item->id }}').innerHTML =
                    '<option value="">Pilih Matkul</option>';
                document.getElementById('matkul_2{{ $item->id }}').disabled = true;

                try {
                    const response = await fetch(`/matkul-list?penguji=${penguji}`);
                    const data = await response.json();

                    data.forEach(matkul => {
                        const option = document.createElement('option');
                        if (value_matkul == matkul.id) {
                            option.selected = true;
                        }
                        option.value = matkul.id;
                        option.text = matkul.nama;
                        document.getElementById('matkul_2{{ $item->id }}').appendChild(option);
                    });

                    document.getElementById('matkul_2{{ $item->id }}').disabled = false;
                } catch (error) {
                    console.error(error);
                }
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', async function() {
                await updateMatkul3{{ $item->id }}();
            });

            async function updateMatkul3{{ $item->id }}() {
                const penguji = document.getElementById('penguji_3{{ $item->id }}').value;
                const value_matkul = document.getElementById('value_matkul_3{{ $item->id }}').value;

                document.getElementById('matkul_3{{ $item->id }}').innerHTML =
                    '<option value="">Pilih Matkul</option>';
                document.getElementById('matkul_3{{ $item->id }}').disabled = true;

                try {
                    const response = await fetch(`/matkul-list?penguji=${penguji}`);
                    const data = await response.json();

                    data.forEach(matkul => {
                        const option = document.createElement('option');
                        if (value_matkul == matkul.id) {
                            option.selected = true;
                        }
                        option.value = matkul.id;
                        option.text = matkul.nama;
                        document.getElementById('matkul_3{{ $item->id }}').appendChild(option);
                    });

                    document.getElementById('matkul_3{{ $item->id }}').disabled = false;
                } catch (error) {
                    console.error(error);
                }
            }
        </script>
    @endforeach
@endsection
