<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Hasil;
use App\Models\Matkul;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;

class MahasiswaController extends Controller
{
    public function index()
    {
        $user = User::where('roles', 'mahasiswa')->where('is_verification', 0)->whereNull('tolak')->get();
        $dosen = User::where('roles', 'dosen')->get();
        return view('Admin.Mahasiswa.index', compact('user', 'dosen'));
    }

    public function telah()
    {
        $user = User::where('roles', 'mahasiswa')->where('is_verification', 1)->get();
        $dosen = User::where('roles', 'dosen')->get();
        return view('Admin.Mahasiswa.index', compact('user', 'dosen'));
    }

    public function tolak()
    {
        $user = User::where('roles', 'mahasiswa')->where('is_verification', 0)->whereNotNull('tolak')->get();
        $dosen = User::where('roles', 'dosen')->get();
        return view('Admin.Mahasiswa.index', compact('user', 'dosen'));
    }

    public function nilai_ujian()
    {
        $user = User::where('roles', 'mahasiswa')->where('is_verification', 1)->get();
        $dosen = User::where('roles', 'dosen')->get();
        return view('Admin.Mahasiswa.index', compact('user', 'dosen'));
    }

    public function update(Request $request, $id)
    {
        if ($request->is_verification == 0) {
            $request->validate([
                'is_verification' => 'required',
                'tolak' => 'nullable',
            ]);

            $user = User::where('id', $id)->first();
            $user->is_verification = $request->is_verification;
            $user->tolak = $request->tolak;

            $user->update();

            if ($user->tolak) {
                Alert::success('Success', 'Tolak akun berhasil');
            }
        } else {
            $request->validate([
                'is_verification' => 'required',
                'penguji_1' => 'required',
                'penguji_2' => 'required',
                'penguji_3' => 'required',
                'matkul_1' => 'required',
                'matkul_2' => 'required',
                'matkul_3' => 'required',
            ]);

            $user = User::where('id', $id)->first();
            $user->is_verification = $request->is_verification;

            $originalData = json_decode($user->penguji, true);

            $originalData['penguji_1']['user_id'] = $request->penguji_1;
            $originalData['penguji_1']['matkul_id'] = $request->matkul_1;

            $originalData['penguji_2']['user_id'] = $request->penguji_2;
            $originalData['penguji_2']['matkul_id'] = $request->matkul_2;

            $originalData['penguji_3']['user_id'] = $request->penguji_3;
            $originalData['penguji_3']['matkul_id'] = $request->matkul_3;

            $updatedJson = json_encode($originalData);

            $user->penguji = $updatedJson;

            $user->update();

            $penguji_ids = [$request->penguji_1, $request->penguji_2, $request->penguji_3];
            $client = new Client();
            $url = "http://8.215.36.120:3000/message";

            foreach ($penguji_ids as $penguji_id) {
                $penguji = User::where('id', $penguji_id)->first();

                if ($penguji) {
                    $wa = $penguji->wa;
                    $message = "Ada Mahasiswa Atas Nama " . $user->nama . " Yang Akan Baru Di Uji, Mohon Atur Jadwal Bimbingannya";

                    $body = [
                        'phoneNumber' => $wa,
                        'message' => $message,
                    ];

                    $client->request('POST', $url, [
                        'form_params' => $body,
                        'verify'  => false,
                    ]);
                }
            }

            Alert::success('Success', 'Verifikasi akun berhasil');
        }

        return redirect()->back();
    }


    public function destroy($id)
    {
        $user = User::find($id);

        // Hapus sk_kompren
        if (Storage::exists('skKompren/' . $user->sk_kompren)) {
            Storage::delete('skKompren/' . $user->sk_kompren);
        }

        $user->delete();
        Alert::success('Success', 'Berhasil menghapus akun');
        return redirect()->back();
    }

    public function pengujian_dosen_api($id)
    {
        $matkul_pengujian = Matkul::where('id', $id)->first();

        if (!$matkul_pengujian) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $dosen = Auth::user();
        $mahasiswa = [];

        $user = User::where('roles', 'mahasiswa')->get();

        foreach ($user as $item) {
            $penguji = json_decode($item->penguji, true);

            foreach ($penguji as $key => $value) {
                if ($dosen->id == $value['user_id'] && $matkul_pengujian->id == $value['matkul_id']) {
                    $data_user = User::where('id', $item->id)->first();

                    $data_user->makeHidden(['nilai', 'is_verification', 'created_at', 'updated_at']);

                    $data_user->penguji = json_decode($data_user->penguji);

                    $mahasiswa[] = $data_user;
                }
            }
        }

        foreach ($mahasiswa as $item) {
            if ($item->sk_kompren) {
                $item->sk_kompren = url('/') . '/storage/skKompren/' . $item->sk_kompren;
            }
        }

        $data_dosen['id'] = $dosen->id;
        $data_dosen['nama'] = $dosen->nama;
        $data_dosen['roles'] = $dosen->roles;

        $matkul_pengujian->makeHidden(['user_id', 'finish_date', 'finish_time', 'created_at', 'updated_at']);

        $data['dosen'] = $data_dosen;
        $data['mahasiswa'] = $mahasiswa;
        $data['matkul'] = $matkul_pengujian;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }

    public function atur_jadwal_api(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'matkul_id' => 'required',
            'tanggal_ujian' => 'required',
            'jam_ujian' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Update Waktu Mulai Ujian Gagal',
                'data' => $validator->errors()
            ], 404);
        }

        $dosen = Auth::user();
        $user = User::where('id', $request->user_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Post Data Gagal, User Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $originalData = json_decode($user->penguji, true);

        if ($dosen->id == $originalData['penguji_1']['user_id'] && $request->matkul_id == $originalData['penguji_1']['matkul_id']) {
            $originalData['penguji_1']['tanggal_ujian'] = $request->tanggal_ujian;
            $originalData['penguji_1']['jam_ujian'] = $request->jam_ujian;
        }
        if ($dosen->id == $originalData['penguji_2']['user_id']  && $request->matkul_id == $originalData['penguji_2']['matkul_id']) {
            $originalData['penguji_2']['tanggal_ujian'] = $request->tanggal_ujian;
            $originalData['penguji_2']['jam_ujian'] = $request->jam_ujian;
        }
        if ($dosen->id == $originalData['penguji_3']['user_id']  && $request->matkul_id == $originalData['penguji_3']['matkul_id']) {
            $originalData['penguji_3']['tanggal_ujian'] = $request->tanggal_ujian;
            $originalData['penguji_3']['jam_ujian'] = $request->jam_ujian;
        }

        $updatedJson = json_encode($originalData);

        $user->penguji = $updatedJson;
        $user->update();

        $matkul = Matkul::where('id', $request->matkul_id)->first();

        $client = new Client();
        $url = "http://8.215.36.120:3000/message";

        $wa = $user->wa;
        $message = "Jadwal Ujian Kompren " . $matkul->matakuliah->nama . " Anda Telah Diatur Pada Tanggal " . $request->tanggal_ujian . " Di Jam " . $request->jam_ujian;

        $body = [
            'phoneNumber' => $wa,
            'message' => $message,
        ];

        $client->request('POST', $url, [
            'form_params' => $body,
            'verify'  => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post Data Berhasil',
            'data' => $user
        ]);
    }

    public function penilaian_dosen_api($id)
    {
        $dosen = Auth::user();
        $mahasiswa = [];
        $matkul_penilaian = Matkul::where('id', $id)->first();

        if (!$matkul_penilaian) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $user = User::where('roles', 'mahasiswa')->get();

        foreach ($user as $item) {
            $penguji = json_decode($item->penguji, true);

            foreach ($penguji as $key => $value) {
                if ($dosen->id == $value['user_id'] && $matkul_penilaian->id == $value['matkul_id']) {
                    $data_user = User::where('id', $item->id)->first();

                    $data_user->makeHidden(['is_verification', 'created_at', 'updated_at']);

                    $data_user->penguji = json_decode($data_user->penguji);
                    $data_user->nilai = json_decode($data_user->nilai);

                    $mahasiswa[] = $data_user;
                }
            }
        }

        $data_dosen['id'] = $dosen->id;
        $data_dosen['nama'] = $dosen->nama;
        $data_dosen['roles'] = $dosen->roles;

        $matkul_penilaian->makeHidden(['user_id', 'finish_date', 'finish_time', 'created_at', 'updated_at']);

        $data['dosen'] = $data_dosen;
        $data['mahasiswa'] = $mahasiswa;
        $data['matkul'] = $matkul_penilaian;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }

    public function nilai_mahasiswa_api($id, $user_id)
    {
        $dosen = Auth::user();

        $matkul_penilaian = Matkul::where('id', $id)->first();

        if (!$matkul_penilaian) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $user = User::where('id', $user_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id User Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        if (!$user->penguji) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id User Bukan Mahasiswa',
                'data' => null
            ], 404);
        }

        $user->penguji = json_decode($user->penguji);
        $user->nilai = json_decode($user->nilai);

        $data_penguji = $user->penguji;
        $data_nilai = $user->nilai;

        if ($data_penguji->penguji_1->user_id == $dosen->id && $data_penguji->penguji_1->matkul_id == $matkul_penilaian->id) {
            $jumlah_benar = $data_nilai->nilai_penguji_1->jumlah_benar;
            $jumlah_salah = $data_nilai->nilai_penguji_1->jumlah_salah;
            $nilai_asli = $data_nilai->nilai_penguji_1->nilai_ujian;
            $remidial = $data_nilai->nilai_penguji_1->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_1->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_1->sk;
        }
        if ($data_penguji->penguji_2->user_id == $dosen->id && $data_penguji->penguji_2->matkul_id == $matkul_penilaian->id) {
            $jumlah_benar = $data_nilai->nilai_penguji_2->jumlah_benar;
            $jumlah_salah = $data_nilai->nilai_penguji_2->jumlah_salah;
            $nilai_asli = $data_nilai->nilai_penguji_2->nilai_ujian;
            $remidial = $data_nilai->nilai_penguji_2->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_2->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_2->sk;
        }
        if ($data_penguji->penguji_3->user_id == $dosen->id && $data_penguji->penguji_3->matkul_id == $matkul_penilaian->id) {
            $jumlah_benar = $data_nilai->nilai_penguji_3->jumlah_benar;
            $jumlah_salah = $data_nilai->nilai_penguji_3->jumlah_salah;
            $nilai_asli = $data_nilai->nilai_penguji_3->nilai_ujian;
            $remidial = $data_nilai->nilai_penguji_3->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_3->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_3->sk;
        }

        $data_dosen['id'] = $dosen->id;
        $data_dosen['nama'] = $dosen->nama;
        $data_dosen['roles'] = $dosen->roles;

        $matkul_penilaian->makeHidden(['user_id', 'finish_date', 'finish_time', 'created_at', 'updated_at']);
        $user->makeHidden(['sk_kompren', 'penguji', 'nilai', 'is_verification', 'created_at', 'updated_at']);

        $data['dosen'] = $data_dosen;
        $data['matkul'] = $matkul_penilaian;
        $data['mahasiswa'] = $user;
        $data['nilai_asli'] = $nilai_asli;
        $data['jumlah_benar'] = $jumlah_benar;
        $data['jumlah_salah'] = $jumlah_salah;
        $data['remidial'] = $remidial;
        $data['nilai_remidial'] = $nilai_remidial;
        $data['sk'] = $sk;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }

    public function remidial_api($id, $user_id)
    {
        $matkul = Matkul::where('id', $id)->first();

        if (!$matkul) {
            return response()->json([
                'success' => false,
                'message' => 'Update Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $user = User::where('id', $user_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Update Data Gagal, Id User Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        if (!$user->penguji) {
            return response()->json([
                'success' => false,
                'message' => 'Update Data Gagal, Id User Bukan Mahasiswa',
                'data' => null
            ], 404);
        }

        $hasil = Hasil::where('user_id', $user_id)->where('matkul_id', $id)->get();

        Hasil::destroy($hasil);

        $dosen = Auth::user();

        $originalData = json_decode($user->penguji, true);
        $originalNilai = json_decode($user->nilai, true);

        if ($dosen->id == $originalData['penguji_1']['user_id'] && $id == $originalData['penguji_1']['matkul_id']) {
            $originalNilai['nilai_penguji_1']['remidial'] = true;
            $originalNilai['nilai_penguji_1']['nilai_remidial'] = null;
        }
        if ($dosen->id == $originalData['penguji_2']['user_id']  && $id == $originalData['penguji_2']['matkul_id']) {
            $originalNilai['nilai_penguji_2']['remidial'] = true;
            $originalNilai['nilai_penguji_2']['nilai_remidial'] = null;
        }
        if ($dosen->id == $originalData['penguji_3']['user_id']  && $id == $originalData['penguji_3']['matkul_id']) {
            $originalNilai['nilai_penguji_3']['remidial'] = true;
            $originalNilai['nilai_penguji_3']['nilai_remidial'] = null;
        }

        $updatedJson = json_encode($originalNilai);

        $user->nilai = $updatedJson;
        $user->update();

        $user->makeHidden(['penguji', 'sk_kompren',  'is_verification', 'created_at', 'updated_at']);

        $client = new Client();
        $url = "http://8.215.36.120:3000/message";

        $wa = $user->wa;
        $message = "Anda Remidial Di Matkul " . $matkul->matakuliah->nama . ", Silahkan Tunggu Jadwal Remidial Anda";

        $body = [
            'phoneNumber' => $wa,
            'message' => $message,
        ];

        $client->request('POST', $url, [
            'form_params' => $body,
            'verify'  => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil Update Data',
            'data' => $user
        ]);
    }

    public function pdf_api(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dosen_penguji' => 'required',
            'mata_kuliah_id' => 'required|exists:matkuls,id',
            'mata_kuliah' => 'required',
            'nama_mahasiswa' => 'required',
            'nim_mahasiswa' => 'required|exists:users,username',
            'nilai_angka' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Cetak SK Gagal',
                'data' => $validator->errors()
            ], 404);
        }

        $user = Auth::user();
        $matkul = Matkul::where('id', $request->mata_kuliah_id)->first();

        $nilai = json_decode($user->nilai);
        $penguji = json_decode($user->penguji);

        if ($matkul->id == $penguji->penguji_1->matkul_id && $matkul->user_id == $penguji->penguji_1->user_id) {
            $tanggal_sk = $nilai->nilai_penguji_1->sk;
            $keterangan = $nilai->nilai_penguji_1->keterangan;
        }
        if ($matkul->id == $penguji->penguji_2->matkul_id && $matkul->user_id == $penguji->penguji_2->user_id) {
            $tanggal_sk = $nilai->nilai_penguji_2->sk;
            $keterangan = $nilai->nilai_penguji_2->keterangan;
        }
        if ($matkul->id == $penguji->penguji_3->matkul_id && $matkul->user_id == $penguji->penguji_3->user_id) {
            $tanggal_sk = $nilai->nilai_penguji_3->sk;
            $keterangan = $nilai->nilai_penguji_3->keterangan;
        }

        if ($request->nilai_angka >= 90 && $request->nilai_angka <= 100) {
            $nilai_huruf = "A";
        } else if ($request->nilai_angka >= 80 && $request->nilai_angka <= 89) {
            $nilai_huruf = "B";
        } else if ($request->nilai_angka >= 70 && $request->nilai_angka <= 79) {
            $nilai_huruf = "C";
        } else if ($request->nilai_angka >= 60 && $request->nilai_angka <= 69) {
            $nilai_huruf = "D";
        } else if ($request->nilai_angka >= 0 && $request->nilai_angka <= 59) {
            $nilai_huruf = "E";
        } else {
            $nilai_huruf = "Nilai tidak valid";
        }

        $pdf = PDF::loadView('Mahasiswa.SkPenilaian.skPDF', compact('request', 'tanggal_sk', 'keterangan', 'nilai_huruf'))->setPaper('A4', 'potrait')->setOptions(['defaultFont' => 'sans-serif']);
        return $pdf->download("Surat Penilaian.pdf");
    }

    public function pdf_matkul_api($id)
    {
        $matkul = Matkul::where('id', $id)->first();

        if (!$matkul) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $user = Auth::user();

        $nilai = json_decode($user->nilai);
        $penguji = json_decode($user->penguji);

        if ($matkul->id == $penguji->penguji_1->matkul_id && $matkul->user_id == $penguji->penguji_1->user_id) {
            $tanggal_sk = $nilai->nilai_penguji_1->sk;
            if (!$tanggal_sk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Get Data Gagal, SK Anda Belum Ada',
                    'data' => null
                ], 404);
            }
            $keterangan = $nilai->nilai_penguji_1->keterangan;
            $nilai_asli = $nilai->nilai_penguji_1->nilai_ujian;
        }
        if ($matkul->id == $penguji->penguji_2->matkul_id && $matkul->user_id == $penguji->penguji_2->user_id) {
            $tanggal_sk = $nilai->nilai_penguji_2->sk;
            if (!$tanggal_sk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Get Data Gagal, SK Anda Belum Ada',
                    'data' => null
                ], 404);
            }
            $keterangan = $nilai->nilai_penguji_2->keterangan;
            $nilai_asli = $nilai->nilai_penguji_2->nilai_ujian;
        }
        if ($matkul->id == $penguji->penguji_3->matkul_id && $matkul->user_id == $penguji->penguji_3->user_id) {
            $tanggal_sk = $nilai->nilai_penguji_3->sk;
            if (!$tanggal_sk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Get Data Gagal, SK Anda Belum Ada',
                    'data' => null
                ], 404);
            }
            $keterangan = $nilai->nilai_penguji_3->keterangan;
            $nilai_asli = $nilai->nilai_penguji_3->nilai_ujian;
        }

        $request = (object)[
            'dosen_penguji' => $matkul->user->nama,
            'mata_kuliah_id' => $matkul->id,
            'mata_kuliah' => $matkul->matakuliah->nama,
            'nama_mahasiswa' => $user->nama,
            'nim_mahasiswa' => $user->username,
            'nilai_angka' => $nilai_asli
        ];

        if ($request->nilai_angka >= 90 && $request->nilai_angka <= 100) {
            $nilai_huruf = "A";
        } else if ($request->nilai_angka >= 80 && $request->nilai_angka <= 89) {
            $nilai_huruf = "B";
        } else if ($request->nilai_angka >= 70 && $request->nilai_angka <= 79) {
            $nilai_huruf = "C";
        } else if ($request->nilai_angka >= 60 && $request->nilai_angka <= 69) {
            $nilai_huruf = "D";
        } else if ($request->nilai_angka >= 0 && $request->nilai_angka <= 59) {
            $nilai_huruf = "E";
        } else {
            $nilai_huruf = "Nilai tidak valid";
        }

        $pdf = PDF::loadView('Mahasiswa.SkPenilaian.skPDF', compact('request', 'tanggal_sk', 'keterangan', 'nilai_huruf'))->setPaper('A4', 'potrait')->setOptions(['defaultFont' => 'sans-serif']);
        return $pdf->download("Surat Penilaian.pdf");
    }

    public function list_mahasiswa_api($id)
    {
        $dosen = Auth::user();
        $mahasiswa = [];
        $matkul_penilaian = Matkul::where('id', $id)->first();

        if (!$matkul_penilaian) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $user = User::where('roles', 'mahasiswa')->get();

        foreach ($user as $item) {
            $penguji = json_decode($item->penguji, true);

            foreach ($penguji as $key => $value) {
                if ($dosen->id == $value['user_id'] && $matkul_penilaian->id == $value['matkul_id']) {
                    $data_user = User::where('id', $item->id)->first();

                    $data_user->makeHidden(['is_verification', 'created_at', 'updated_at', 'penguji', 'nilai', 'roles', 'sk_kompren']);

                    $mahasiswa[] = $data_user;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $mahasiswa
        ]);
    }

    public function detail_mahasiswa_api($id, $user_id)
    {
        $dosen = Auth::user();

        $matkul_penilaian = Matkul::where('id', $id)->first();

        if (!$matkul_penilaian) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $user = User::where('id', $user_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id User Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        if (!$user->penguji) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id User Bukan Mahasiswa',
                'data' => null
            ], 404);
        }

        $user->penguji = json_decode($user->penguji);
        $user->nilai = json_decode($user->nilai);

        $data_penguji = $user->penguji;
        $data_nilai = $user->nilai;

        if ($data_penguji->penguji_1->user_id == $dosen->id && $data_penguji->penguji_1->matkul_id == $matkul_penilaian->id) {
            $nilai_asli = $data_nilai->nilai_penguji_1->nilai_ujian;
            $remidial = $data_nilai->nilai_penguji_1->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_1->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_1->sk;
        }
        if ($data_penguji->penguji_2->user_id == $dosen->id && $data_penguji->penguji_2->matkul_id == $matkul_penilaian->id) {
            $nilai_asli = $data_nilai->nilai_penguji_2->nilai_ujian;
            $remidial = $data_nilai->nilai_penguji_2->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_2->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_2->sk;
        }
        if ($data_penguji->penguji_3->user_id == $dosen->id && $data_penguji->penguji_3->matkul_id == $matkul_penilaian->id) {
            $nilai_asli = $data_nilai->nilai_penguji_3->nilai_ujian;
            $remidial = $data_nilai->nilai_penguji_3->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_3->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_3->sk;
        }

        $user->makeHidden(['sk_kompren', 'tolak', 'penguji', 'nilai', 'is_verification', 'created_at', 'updated_at']);

        if ($sk) {
            $status = "SK Nilai Tersedia";
            $nilai = $nilai_asli;
        } elseif ($remidial) {
            if ($nilai_remidial !== null) {
                $status = "Telah Remidial";
                $nilai = $nilai_remidial;
            } else {
                $status = "Belum Remidial";
                $nilai = 0;
            }
        } elseif ($nilai_asli !== null) {
            $status = "Selesai Ujian";
            $nilai = $nilai_asli;
        } else {
            $status = "Belum Ujian";
            $nilai = 0;
        }

        if ($user->foto) {
            $user->foto = url('/') . '/storage/foto/' . $user->foto;
        } else {
            $user->foto = url('/') . '/assets/img/profile.png';
        }

        $data['mahasiswa'] = $user;
        $data['status'] = $status;
        $data['nilai'] = $nilai;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }
}
