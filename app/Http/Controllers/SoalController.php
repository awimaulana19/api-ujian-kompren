<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Soal;
use App\Models\User;
use App\Models\Hasil;
use App\Models\Matkul;
use App\Models\Jawaban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;

class SoalController extends Controller
{
    // Algoritma LCM
    private function lcm($seed, $n)
    {
        $a = 16807; // multiplier
        $c = 1;     // konstanta penambahan
        $m = 2147483647; // modulus (2^31 - 1)

        $result = [];
        $x = $seed;

        for ($i = 0; $i < $n; $i++) {
            $x = ($a * $x + $c) % $m;
            $result[] = $x;
        }

        return $result;
    }

    // Fungsi untuk mengacak soal dengan memakai rumus LCM
    public function acak($id)
    {
        $user = Auth::user();
        $matkul = Matkul::where('id', $id)->first();

        $data_penguji = json_decode($user->penguji);
        $data_nilai = json_decode($user->nilai);

        if ($data_penguji->penguji_1->user_id == $matkul->user_id && $data_penguji->penguji_1->matkul_id == $matkul->id) {
            $remidial = $data_nilai->nilai_penguji_1->remidial;
        }
        if ($data_penguji->penguji_2->user_id == $matkul->user_id && $data_penguji->penguji_2->matkul_id == $matkul->id) {
            $remidial = $data_nilai->nilai_penguji_2->remidial;
        }
        if ($data_penguji->penguji_3->user_id == $matkul->user_id && $data_penguji->penguji_3->matkul_id == $matkul->id) {
            $remidial = $data_nilai->nilai_penguji_3->remidial;
        }

        if ($remidial) {
            $soal = Soal::where('matkul_id', $id)
                ->whereIn('tingkat', ['Sulit', 'Menengah', 'Mudah'])
                ->get();
        } else {
            $soal = Soal::where('matkul_id', $id)
                ->whereIn('tingkat', ['Sulit', 'Menengah', 'Mudah'])
                ->get();
        }

        // Seed untuk generator (Anda dapat menggunakan nilai awal yang berbeda untuk mendapatkan hasil acak yang berbeda)
        $seed = time();

        // Hitung jumlah soal
        $jumlahSoal = count($soal);

        // Lakukan pengacakan untuk indeks soal
        $soalIndices = $this->lcm($seed, $jumlahSoal);

        // Urutkan soal berdasarkan hasil pengacakan
        $soal = $soal->sortBy(function ($item, $key) use ($soalIndices) {
            return $soalIndices[$key];
        });

        $soal = $soal->take($matkul->jumlah_soal);

        // Lakukan pengacakan untuk jawaban pada setiap soal
        foreach ($soal as $item) {
            $seed = $seed + 1;
            $jawabanIndices = $this->lcm($seed, count($item->jawaban));
            $item->jawaban = $item->jawaban->sortBy(function ($jawaban, $key) use ($jawabanIndices) {
                return $jawabanIndices[$key];
            });
        }

        return ['soal' => $soal, 'jumlah_soal' => count($soal)];
    }

    public function start_gagal()
    {
        return response()->json([
            'success' => false,
            'message' => 'Anda Belum Diizinkan Untuk Ujian',
            'data' => null
        ], 404);
    }

    public function soal_matkul_api($id)
    {
        $matkul = Matkul::where('id', $id)->with('soal')->first();

        if (!$matkul) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        foreach ($matkul->soal as $soal) {
            if ($soal->gambar_soal) {
                $soal->gambar_soal = url('/') . '/storage/' . $soal->gambar_soal;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $matkul
        ]);
    }

    public function store_api(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matkul_id' => 'required',
            'soal' => 'required|string|max:255',
            'tingkat' => 'required',
            'gambar_soal' => 'mimes:png,jpg,jpeg|max:10240',
            'jawaban.*' => 'required|string|max:255',
            'gambar_jawaban.*' => 'mimes:png,jpg,jpeg|max:10240',
            'benar' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Store Soal Gagal',
                'data' => $validator->errors()
            ], 404);
        }

        $soal = new Soal();
        $soal->matkul_id = $request->matkul_id;
        $soal->soal = $request->soal;
        $soal->tingkat = $request->tingkat;
        if ($request->file('gambar_soal')) {
            $soal->gambar_soal = $request->file('gambar_soal')->store('gambar-soal');
        }
        $soal->save();

        foreach ($request->jawaban as $index => $jawabanText) {
            $jawaban = new Jawaban();
            $jawaban->soal_id = $soal->id;
            $jawaban->jawaban = $jawabanText;
            $jawaban->is_correct = ($request->benar == chr(65 + $index)) ? true : false;

            if ($request->file('gambar_jawaban') && isset($request->file('gambar_jawaban')[$index])) {
                $jawaban->gambar_jawaban = $request->file('gambar_jawaban')[$index]->store('gambar-jawaban');
            }
            $jawaban->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Store Soal Berhasil',
            'data' => $soal
        ]);
    }

    public function edit_api($id)
    {
        $soal = Soal::where('id', $id)->first();

        if (!$soal) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Soal Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $jawaban = Jawaban::where('soal_id', $id)->get();

        if ($soal->gambar_soal) {
            $soal->gambar_soal = url('/') . '/storage/' . $soal->gambar_soal;
        }

        $soal->makeHidden(['created_at', 'updated_at']);

        foreach ($jawaban as &$jawab) {
            if ($jawab['gambar_jawaban']) {
                $jawab['gambar_jawaban'] = url('/') . '/storage/' . $jawab['gambar_jawaban'];
            }

            unset($jawab['created_at'], $jawab['updated_at']);
        }

        $data['soal'] = $soal;
        $data['jawaban'] = $jawaban;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }

    public function update_api(Request $request, $id)
    {
        $soal = Soal::where('id', $id)->first();

        if (!$soal) {
            return response()->json([
                'success' => false,
                'message' => 'Update Data Gagal, Id Soal Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'matkul_id' => 'required',
            'soal' => 'required|string|max:255',
            'tingkat' => 'required',
            'gambar_soal' => 'mimes:png,jpg,jpeg|max:10240',
            'jawaban.*' => 'required|string|max:255',
            'gambar_jawaban.*' => 'mimes:png,jpg,jpeg|max:10240',
            'benar' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Update Soal Gagal',
                'data' => $validator->errors()
            ], 404);
        }

        $soal->matkul_id = $request->matkul_id;
        $soal->soal = $request->soal;
        $soal->tingkat = $request->tingkat;
        if ($request->file('gambar_soal')) {
            $soal->gambar_soal = $request->file('gambar_soal')->store('gambar-soal');
        }
        $soal->save();

        $soal->jawaban()->delete();

        foreach ($request->jawaban as $index => $jawabanText) {
            $jawaban = new Jawaban();
            $jawaban->soal_id = $soal->id;
            $jawaban->jawaban = $jawabanText;
            $jawaban->is_correct = ($request->benar == chr(65 + $index)) ? true : false;

            if ($request->file('gambar_jawaban') && isset($request->file('gambar_jawaban')[$index])) {
                $jawaban->gambar_jawaban = $request->file('gambar_jawaban')[$index]->store('gambar-jawaban');
            }
            $jawaban->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Update Soal Berhasil',
            'data' => $soal
        ]);
    }

    public function destroy_api($id)
    {
        $soal = Soal::where('id', $id)->first();

        if (!$soal) {
            return response()->json([
                'success' => false,
                'message' => 'Delete Data Gagal, Id Soal Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $soal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delete Soal Berhasil',
            'data' => null
        ]);
    }

    public function set_finish_api(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'durasi' => 'required',
            'jumlah_soal' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Update Waktu Mulai Ujian Gagal',
                'data' => $validator->errors()
            ], 404);
        }

        $finish = Matkul::where('id', $id)->first();

        if (!$finish) {
            return response()->json([
                'success' => false,
                'message' => 'Update Waktu Mulai Ujian Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $finish->durasi = $request->durasi;
        $finish->jumlah_soal = $request->jumlah_soal;
        $finish->update();

        $finish->makeHidden(['created_at', 'updated_at', 'finish_time', 'finish_date']);

        return response()->json([
            'success' => true,
            'message' => 'Update Waktu Mulai Ujian Berhasil',
            'data' => $finish
        ]);
    }

    public function set_end_api($id)
    {
        $finish = Matkul::where('id', $id)->first();

        if (!$finish) {
            return response()->json([
                'success' => false,
                'message' => 'Akhiri Waktu Mulai Ujian Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $finish->durasi = null;
        $finish->jumlah_soal = null;
        $finish->update();

        $finish->makeHidden(['created_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'message' => 'Akhiri Waktu Mulai Ujian Berhasil',
            'data' => $finish
        ]);
    }

    public function ujian_mahasiswa_api($id)
    {
        $mahasiswa = Auth::user();
        $matkul = Matkul::where('id', $id)->with('user')->first();

        if (!$matkul) {
            return response()->json([
                'success' => false,
                'message' => 'Get Data Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $data_penguji = json_decode($mahasiswa->penguji);
        $data_nilai = json_decode($mahasiswa->nilai);

        if ($data_penguji->penguji_1->user_id == $matkul->user_id && $data_penguji->penguji_1->matkul_id == $matkul->id) {
            $nilai_asli = $data_nilai->nilai_penguji_1->nilai_ujian;
            $jumlah_benar = $data_nilai->nilai_penguji_1->jumlah_benar;
            $jumlah_salah = $data_nilai->nilai_penguji_1->jumlah_salah;
            $remidial = $data_nilai->nilai_penguji_1->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_1->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_1->sk;
            $tanggal_ujian = $data_penguji->penguji_1->tanggal_ujian ?? 'Belum Ada';
            $jam_ujian = $data_penguji->penguji_1->jam_ujian ?? 'Belum Ada';
        }
        if ($data_penguji->penguji_2->user_id == $matkul->user_id && $data_penguji->penguji_2->matkul_id == $matkul->id) {
            $nilai_asli = $data_nilai->nilai_penguji_2->nilai_ujian;
            $jumlah_benar = $data_nilai->nilai_penguji_2->jumlah_benar;
            $jumlah_salah = $data_nilai->nilai_penguji_2->jumlah_salah;
            $remidial = $data_nilai->nilai_penguji_2->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_2->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_2->sk;
            $tanggal_ujian = $data_penguji->penguji_2->tanggal_ujian ?? 'Belum Ada';
            $jam_ujian = $data_penguji->penguji_2->jam_ujian ?? 'Belum Ada';
        }
        if ($data_penguji->penguji_3->user_id == $matkul->user_id && $data_penguji->penguji_3->matkul_id == $matkul->id) {
            $nilai_asli = $data_nilai->nilai_penguji_3->nilai_ujian;
            $jumlah_benar = $data_nilai->nilai_penguji_3->jumlah_benar;
            $jumlah_salah = $data_nilai->nilai_penguji_3->jumlah_salah;
            $remidial = $data_nilai->nilai_penguji_3->remidial;
            $nilai_remidial = $data_nilai->nilai_penguji_3->nilai_remidial;
            $sk = $data_nilai->nilai_penguji_3->sk;
            $tanggal_ujian = $data_penguji->penguji_3->tanggal_ujian ?? 'Belum Ada';
            $jam_ujian = $data_penguji->penguji_3->jam_ujian ?? 'Belum Ada';
        }

        $data_mahasiswa['id'] = $mahasiswa->id;
        $data_mahasiswa['nama'] = $mahasiswa->nama;
        $data_mahasiswa['username'] = $mahasiswa->username;
        $data_mahasiswa['roles'] = $mahasiswa->roles;

        $matkul->makeHidden(['user_id', 'created_at', 'updated_at']);
        $matkul->user->makeHidden(['username', 'penguji', 'nilai', 'sk_kompren',  'is_verification', 'created_at', 'updated_at']);

        $data['mahasiswa'] = $data_mahasiswa;
        $data['matkul'] = $matkul;
        $data['nilai_asli'] = $nilai_asli;
        $data['jumlah_benar'] = $jumlah_benar;
        $data['jumlah_salah'] = $jumlah_salah;
        $data['remidial'] = $remidial;
        $data['nilai_remidial'] = $nilai_remidial;
        $data['sk'] = $sk;
        $data['tanggal_ujian'] = $tanggal_ujian;
        $data['jam_ujian'] = $jam_ujian;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }

    public function soal_mahasiswa_api($id)
    {
        $user = Auth::user();
        $nilai = Hasil::where('user_id', $user->id)->where('matkul_id', $id)->get();

        if ($nilai->isEmpty()) {
            $matkul = Matkul::where('id', $id)->first();
            $hasil_acak = $this->acak($id);
            $soal = $hasil_acak['soal'];

            $data_mahasiswa['id'] = $user->id;
            $data_mahasiswa['nama'] = $user->nama;
            $data_mahasiswa['roles'] = $user->roles;

            $matkul->makeHidden(['user_id', 'created_at', 'updated_at', 'finish_date', 'finish_time']);

            $data['mahasiswa'] = $data_mahasiswa;
            $data['matkul'] = $matkul;
            $data_soal = $soal->toArray();
            $data_soal = array_values($data_soal);

            foreach ($data_soal as &$soal) {
                if ($soal['gambar_soal']) {
                    $soal['gambar_soal'] = url('/') . '/storage/' . $soal['gambar_soal'];
                }

                unset($soal['tingkat'], $soal['matkul_id'], $soal['created_at'], $soal['updated_at']);

                foreach ($soal['jawaban'] as &$jawaban) {
                    if ($jawaban['gambar_jawaban']) {
                        $jawaban['gambar_jawaban'] = url('/') . '/storage/' . $jawaban['gambar_jawaban'];
                    }

                    unset($jawaban['is_correct'], $jawaban['soal_id'], $jawaban['created_at'], $jawaban['updated_at']);
                }
            }

            $data['soal'] = $data_soal;

            $data_penguji = json_decode($user->penguji);

            if ($data_penguji->penguji_1->user_id == $matkul->user_id && $data_penguji->penguji_1->matkul_id == $matkul->id) {
                $tanggal_ujian = $data_penguji->penguji_1->tanggal_ujian ?? 'Belum Ada';
                $jam_ujian = $data_penguji->penguji_1->jam_ujian ?? 'Belum Ada';
            }
            if ($data_penguji->penguji_2->user_id == $matkul->user_id && $data_penguji->penguji_2->matkul_id == $matkul->id) {
                $tanggal_ujian = $data_penguji->penguji_2->tanggal_ujian ?? 'Belum Ada';
                $jam_ujian = $data_penguji->penguji_2->jam_ujian ?? 'Belum Ada';
            }
            if ($data_penguji->penguji_3->user_id == $matkul->user_id && $data_penguji->penguji_3->matkul_id == $matkul->id) {
                $tanggal_ujian = $data_penguji->penguji_3->tanggal_ujian ?? 'Belum Ada';
                $jam_ujian = $data_penguji->penguji_3->jam_ujian ?? 'Belum Ada';
            }

            if ($tanggal_ujian == 'Belum Ada' || $jam_ujian == 'Belum Ada') {
                return response()->json([
                    'success' => false,
                    'message' => 'Get Data Gagal, Anda Sudah Mengerjakan Ujian',
                    'data' => null
                ], 404);
            }

            $datetime_ujian = $tanggal_ujian . ' ' . $jam_ujian;
            $formats = [
                'Y-m-d H:i',
                'Y/m/d H:i',
            ];

            $waktu_mulai = null;
            foreach ($formats as $format) {
                try {
                    $waktu_mulai = Carbon::createFromFormat($format, $datetime_ujian);
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$waktu_mulai) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format Tanggal Ujian Salah',
                    'data' => null
                ], 404);
            }

            $durasi = $matkul->durasi;

            $waktu_selesai = $waktu_mulai->copy()->addMinutes($durasi);

            if ($waktu_selesai->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waktu habis',
                    'data' => null
                ], 404);
            }

            $data['tanggal_selesai'] = $waktu_selesai->format('Y-m-d');
            $data['jam_selesai'] = $waktu_selesai->format('H:i');

            return response()->json([
                'success' => true,
                'message' => 'Get Data Berhasil',
                'data' => $data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Get Data Gagal, Anda Sudah Mengerjakan Ujian',
            'data' => null
        ], 404);
    }

    public function jawab_mahasiswa_api(Request $request, $id)
    {
        $matkul = Matkul::where('id', $id)->first();

        if (!$matkul) {
            return response()->json([
                'success' => false,
                'message' => 'Submit Jawaban Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $user = User::where('id', $request->user_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Submit Jawaban Gagal, Id User Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        if (!$user->penguji) {
            return response()->json([
                'success' => false,
                'message' => 'Submit Jawaban Gagal, Id User Bukan Mahasiswa',
                'data' => null
            ], 404);
        }

        $hasil = Hasil::where('user_id', $request->user_id)->where('matkul_id', $id)->get();
        if ($hasil->isEmpty()) {
            $soal = Soal::where('matkul_id', $matkul->id)->get();

            foreach ($soal as $item) {
                foreach ($item->jawaban as $pilih) {
                    if ($request->{'soal' . $item->id} == $pilih->id) {
                        if ($pilih->is_correct) {
                            $hasil = new Hasil();
                            $hasil->user_id = $user->id;
                            $hasil->matkul_id = $matkul->id;
                            $hasil->soal_id = $pilih->soal_id;
                            $hasil->benar = true;

                            $hasil->save();
                        } else {
                            $hasil = new Hasil();
                            $hasil->user_id = $user->id;
                            $hasil->matkul_id = $matkul->id;
                            $hasil->soal_id = $pilih->soal_id;
                            $hasil->benar = false;

                            $hasil->save();
                        }
                    }
                }
            }

            $hasil_acak = $this->acak($id);

            $jumlah_soal = $hasil_acak['jumlah_soal'];
            $jumlahBenar = 0;
            $jumlahSalah = 0;

            $hasil = Hasil::where('user_id', $user->id)->where('matkul_id', $matkul->id)->get();

            foreach ($hasil as $item) {
                if ($item->benar) {
                    $jumlahBenar = $jumlahBenar + 1;
                }
                if (!$item->benar) {
                    $jumlahSalah = $jumlahSalah + 1;
                }
            }

            $nilai_ujian = ($jumlahBenar / $jumlah_soal) * 100;

            $originalData = json_decode($user->nilai, true);

            $penguji = json_decode($user->penguji);
            if ($matkul->id == $penguji->penguji_1->matkul_id && $matkul->user_id == $penguji->penguji_1->user_id) {
                $originalData['nilai_penguji_1']['jumlah_benar'] = $jumlahBenar;
                $originalData['nilai_penguji_1']['jumlah_salah'] = $jumlahSalah;
                if ($originalData['nilai_penguji_1']['remidial']) {
                    $originalData['nilai_penguji_1']['nilai_remidial'] = $nilai_ujian;
                } else {
                    $originalData['nilai_penguji_1']['nilai_ujian'] = $nilai_ujian;
                }

                if ($nilai_ujian < 60) {
                    $hasil = Hasil::where('user_id', $user->id)->where('matkul_id', $matkul->id)->get();
                    Hasil::destroy($hasil);
                    $originalData['nilai_penguji_1']['remidial'] = true;
                    $originalData['nilai_penguji_1']['nilai_remidial'] = null;
                }
            }
            if ($matkul->id == $penguji->penguji_2->matkul_id && $matkul->user_id == $penguji->penguji_2->user_id) {
                $originalData['nilai_penguji_2']['jumlah_benar'] = $jumlahBenar;
                $originalData['nilai_penguji_2']['jumlah_salah'] = $jumlahSalah;
                if ($originalData['nilai_penguji_2']['remidial']) {
                    $originalData['nilai_penguji_2']['nilai_remidial'] = $nilai_ujian;
                } else {
                    $originalData['nilai_penguji_2']['nilai_ujian'] = $nilai_ujian;
                }

                if ($nilai_ujian < 60) {
                    $hasil = Hasil::where('user_id', $user->id)->where('matkul_id', $matkul->id)->get();
                    Hasil::destroy($hasil);
                    $originalData['nilai_penguji_2']['remidial'] = true;
                    $originalData['nilai_penguji_2']['nilai_remidial'] = null;
                }
            }
            if ($matkul->id == $penguji->penguji_3->matkul_id && $matkul->user_id == $penguji->penguji_3->user_id) {
                $originalData['nilai_penguji_3']['jumlah_benar'] = $jumlahBenar;
                $originalData['nilai_penguji_3']['jumlah_salah'] = $jumlahSalah;
                if ($originalData['nilai_penguji_3']['remidial']) {
                    $originalData['nilai_penguji_3']['nilai_remidial'] = $nilai_ujian;
                } else {
                    $originalData['nilai_penguji_3']['nilai_ujian'] = $nilai_ujian;
                }

                if ($nilai_ujian < 60) {
                    $hasil = Hasil::where('user_id', $user->id)->where('matkul_id', $matkul->id)->get();
                    Hasil::destroy($hasil);
                    $originalData['nilai_penguji_3']['remidial'] = true;
                    $originalData['nilai_penguji_3']['nilai_remidial'] = null;
                }
            }

            $updatedJson = json_encode($originalData);

            $user->nilai = $updatedJson;
            $user->update();

            $user->makeHidden(['penguji', 'sk_kompren',  'is_verification', 'created_at', 'updated_at']);

            return response()->json([
                'success' => true,
                'message' => 'Submit Jawaban Berhasil, Ujian Berhasil Di Kerjakan',
                'data' => $user
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Submit Jawaban Gagal, Anda Sudah Mengerjakan Ujian',
            'data' => null
        ], 404);
    }

    private function lcm_test($seed, $n)
    {
        $a = 16807; // multiplier
        $c = 1;     // konstanta penambahan
        $m = 2147483647; // modulus (2^31 - 1)

        $result = [];
        $x = $seed;

        for ($i = 0; $i < $n; $i++) {
            $x = ($a * $x + $c) % $m;
            $result[] = $x;
        }

        return $result;
    }

    // Fungsi untuk mengacak soal dengan memakai rumus LCM
    public function acak_test($id)
    {
        $user = User::where('username', '60900120002')->first();
        $matkul = Matkul::where('id', $id)->first();

        $data_penguji = json_decode($user->penguji);
        $data_nilai = json_decode($user->nilai);

        if ($data_penguji->penguji_1->user_id == $matkul->user_id && $data_penguji->penguji_1->matkul_id == $matkul->id) {
            $remidial = $data_nilai->nilai_penguji_1->remidial;
        }
        if ($data_penguji->penguji_2->user_id == $matkul->user_id && $data_penguji->penguji_2->matkul_id == $matkul->id) {
            $remidial = $data_nilai->nilai_penguji_2->remidial;
        }
        if ($data_penguji->penguji_3->user_id == $matkul->user_id && $data_penguji->penguji_3->matkul_id == $matkul->id) {
            $remidial = $data_nilai->nilai_penguji_3->remidial;
        }

        if ($remidial) {
            $soal = Soal::where('matkul_id', $id)
                ->whereIn('tingkat', ['Menengah', 'Mudah'])
                ->with('jawaban')
                ->get();
        } else {
            $soal = Soal::where('matkul_id', $id)
                ->whereIn('tingkat', ['Sulit', 'Menengah'])
                ->with('jawaban')
                ->get();
        }

        // Seed untuk generator (Anda dapat menggunakan nilai awal yang berbeda untuk mendapatkan hasil acak yang berbeda)
        $seed = time();

        // Hitung jumlah soal
        $jumlahSoal = count($soal);

        // Lakukan pengacakan untuk indeks soal
        $soalIndices = $this->lcm_test($seed, $jumlahSoal);

        // Urutkan soal berdasarkan hasil pengacakan
        $soal = $soal->sortBy(function ($item, $key) use ($soalIndices) {
            return $soalIndices[$key];
        });

        $soal = $soal->take(15);

        $soal = $soal->toArray();

        foreach ($soal as &$item) {
            $keys = array_keys($item['jawaban']);
            shuffle($keys);
            $jawaban_teracak = [];
            foreach ($keys as $key) {
                $jawaban_teracak[$key] = $item['jawaban'][$key];
            }
            $item['jawaban'] = $jawaban_teracak;
        }

        return ['soal' => $soal, 'jumlah_soal' => count($soal)];
    }
}
