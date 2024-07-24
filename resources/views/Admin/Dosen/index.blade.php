@extends('Admin.Layouts.app')

@section('title', 'Data Dosen')

@section('content')
    <div class="row">
        <div class="col-lg-12 col-md-12 mb-4">
            <div class="card p-4">
                <div class="d-flex justify-content-between">
                    <h5>Data Dosen</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        Tambah Data
                    </button>
                </div>
                <!-- Modal -->
                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="exampleModalLabel">Tambah Akun Dosen
                                </h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ url('/admin/dosen') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="roles" value="dosen" />
                                    <input type="hidden" name="is_verification" value="1" />
                                    <div class="mb-3">
                                        <label for="nama" class="form-label">Nama Dosen</label>
                                        <input type="text" id="nama" name="nama" class="form-control"
                                            placeholder="Masukkan Nama Dosen" />
                                    </div>
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username/Nip</label>
                                        <input type="text" id="username" name="username" class="form-control"
                                            placeholder="Masukkan Username/Nip" />
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" id="password" name="password" class="form-control"
                                            placeholder="Masukkan Password" />
                                    </div>
                                    <div class="mb-3">
                                        <label for="matkul" class="form-label">Mata Kuliah</label>
                                        <br>
                                        @foreach ($matakuliah as $item)
                                            <div class="form-check form-check-inline">
                                                <input type="checkbox" class="form-check-input" name="matakuliah_id[]"
                                                    id="matakuliah_{{ $item->id }}" value="{{ $item->id }}">
                                                <label class="form-check-label"
                                                    for="matakuliah_{{ $item->id }}">{{ $item->nama }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Tambah</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive text-nowrap mt-4">
                    <table class="table table-hover" id="table1">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">Nama Dosen</th>
                                <th class="text-center">Username/NIP</th>
                                <th class="text-center">Mata Kuliah</th>
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
                                        @php
                                            $matkul_list = $item->matkul->pluck('id')->toArray();
                                        @endphp
                                        @foreach ($matkul_list as $matkul)
                                            @php
                                                $matkulItem = $item->matkul->where('id', $matkul)->first();
                                            @endphp
                                            <div class="btn-group dropend">
                                                <a class="dropdown-toggle me-2" href="#" role="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ $matkulItem->matakuliah->nama }}
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item"
                                                            href="/admin/dosen/mahasiswa-diuji/{{ $item->id }}/{{ $matkulItem->id }}">Mahasiswa</a>
                                                    </li>
                                                    <li><a class="dropdown-item"
                                                            href="/admin/dosen/bank-soal/{{ $matkulItem->id }}">Bank
                                                            Soal</a></li>
                                                </ul>
                                            </div>
                                        @endforeach
                                    </td>

                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#exampleModal{{ $item->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <a href="/admin/dosen/delete/{{ $item->id }}" class="btn btn-sm btn-danger"
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
                                                    <form action="/admin/dosen/update/{{ $item->id }}"
                                                        method="POST">
                                                        @csrf
                                                        <div class="mb-3">
                                                            <label for="nama" class="form-label">Nama Dosen</label>
                                                            <input type="text" value="{{ $item->nama }}"
                                                                name="nama" id="nama" class="form-control" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="username" class="form-label">Username</label>
                                                            <input type="text" value="{{ $item->username }}"
                                                                name="username" id="username" class="form-control" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="password" class="form-label">Password</label>
                                                            <input type="password" id="password" name="password"
                                                                class="form-control"
                                                                placeholder="Masukkan Password Baru" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="matkul" class="form-label">Mata Kuliah</label>
                                                            <br>
                                                            @foreach ($matakuliah as $mata)
                                                                <div class="form-check">
                                                                    <input type="checkbox" class="form-check-input"
                                                                        name="matakuliah_id[]"
                                                                        id="matakuliah_{{ $mata->id }}"
                                                                        value="{{ $mata->id }}"
                                                                        {{ in_array($mata->id, $item->matkul->pluck('matakuliah.id')->toArray()) ? 'checked' : '' }}>
                                                                    <label class="form-check-label"
                                                                        for="matakuliah_{{ $mata->id }}">{{ $mata->nama }}</label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </form>
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
