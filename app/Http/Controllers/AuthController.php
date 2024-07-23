<?php

namespace App\Http\Controllers;

use App\Models\Soal;
use App\Models\User;
use App\Models\Matkul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function beranda()
    {
        return view('beranda');
    }

    public function halaman_login()
    {
        return view('login');
    }

    public function dashboard_admin()
    {
        $soal_mudah = Soal::where('tingkat', '=', 'mudah')->count();
        $soal_menengah = Soal::where('tingkat', '=', 'menengah')->count();
        $soal_sulit = Soal::where('tingkat', '=', 'menengah')->count();
        return view('Admin.Dashboard.dashboard', compact('soal_mudah', 'soal_menengah', 'soal_sulit'));
    }

    public function login_action(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            if (Auth::user()->roles == 'admin') {
                return redirect('/dashboard-admin');
            }
        }
        return back()->withErrors([
            'password' => 'Username atau Password anda salah',
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

    public function regis_api()
    {
        $dosen = User::where('roles', 'dosen')->get();

        foreach ($dosen as &$item) {
            unset($item['created_at'], $item['updated_at'], $item['tolak'], $item['is_verification'], $item['nilai'], $item['penguji'], $item['foto'], $item['sk_kompren'], $item['roles'], $item['username']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $dosen
        ]);
    }

    public function register_api(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'username' => 'required|size:11',
            'password' => 'required|min:8',
            'sk_kompren' => 'required|file|mimes:pdf',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png',
            'wa' => 'required|unique:users,wa',
            'penguji_1' => 'required',
            'penguji_2' => 'required',
            'penguji_3' => 'required',
            'matkul_1' => 'required',
            'matkul_2' => 'required',
            'matkul_3' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran Gagal',
                'data' => $validator->errors()
            ], 404);
        }

        if ($request->has('sk_kompren')) {
            $file = $request->file('sk_kompren');
            $nama_file = time() . "_SK_" . $request->username . ".pdf";

            $file->storeAs('skKompren', $nama_file);

            $hashedPassword = bcrypt($request->password);

            $penguji = json_encode([
                'penguji_1' => ['user_id' => $request->penguji_1, 'matkul_id' => $request->matkul_1, 'dapat_ujian' => false],
                'penguji_2' => ['user_id' => $request->penguji_2, 'matkul_id' => $request->matkul_2, 'dapat_ujian' => false],
                'penguji_3' => ['user_id' => $request->penguji_3, 'matkul_id' => $request->matkul_3, 'dapat_ujian' => false],
            ]);

            $nilai = json_encode([
                'nilai_penguji_1' => ['jumlah_benar' => 0, 'jumlah_salah' => 0, 'nilai_ujian' => null, 'remidial' => false, 'nilai_remidial' => null, 'sk' => null],
                'nilai_penguji_2' => ['jumlah_benar' => 0, 'jumlah_salah' => 0, 'nilai_ujian' => null, 'remidial' => false, 'nilai_remidial' => null, 'sk' => null],
                'nilai_penguji_3' => ['jumlah_benar' => 0, 'jumlah_salah' => 0, 'nilai_ujian' => null, 'remidial' => false, 'nilai_remidial' => null, 'sk' => null],
            ]);

            $user = User::where('username', $request->username)->first();

            if ($user) {
                $user->nama = $request->nama;
                $user->wa = $request->wa;
                $user->password = $hashedPassword;
                $user->penguji = $penguji;
                $user->nilai = $nilai;
                $user->sk_kompren = $nama_file;
                $user->is_verification = false;
                $user->tolak = null;

                if ($request->has('foto')) {
                    $foto = $request->file('foto');
                    $nama_foto = time() . "_foto_" . $request->username . "." . $foto->getClientOriginalExtension();

                    $foto->storeAs('foto', $nama_foto);

                    $user->foto = $nama_foto;
                }

                $user->update();

                $data['nama'] = $user->nama;
                $data['username'] = $user->username;
                $data['roles'] = $user->roles;
            } else {
                $nama_foto = null;
                if ($request->has('foto')) {
                    $foto = $request->file('foto');
                    $nama_foto = time() . "_foto_" . $request->username . "." . $foto->getClientOriginalExtension();

                    $foto->storeAs('foto', $nama_foto);
                }

                $regis = new User();
                $regis->nama = $request->nama;
                $regis->wa = $request->wa;
                $regis->username = $request->username;
                $regis->password = $hashedPassword;
                $regis->roles = 'mahasiswa';
                $regis->penguji = $penguji;
                $regis->nilai = $nilai;
                $regis->sk_kompren = $nama_file;
                $regis->foto = $nama_foto;
                $regis->save();

                $data['nama'] = $regis->nama;
                $data['username'] = $regis->username;
                $data['roles'] = $regis->roles;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran Berhasil',
            'data' => $data
        ]);
    }

    public function login_api(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Login Gagal',
                'data' => $validator->errors()
            ], 404);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah',
                'data' => null
            ], 404);
        }

        $data['token'] = $user->createToken('auth_token')->plainTextToken;
        $data['nama'] = $user->nama;
        $data['username'] = $user->username;
        $data['roles'] = $user->roles;

        if (!$user->is_verification) {
            return response()->json([
                'success' => true,
                'message' => 'Akun Anda Belum Di Verifikasi Oleh Admin',
                'data' => $data
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => $data
        ]);
    }

    public function login_gagal()
    {
        return response()->json([
            'success' => false,
            'message' => 'Token Tidak Valid',
            'data' => null
        ], 404);
    }

    public function logout_api(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout Berhasil',
            'data' => null
        ]);
    }

    public function get_matkul_api()
    {
        $data = auth()->user()->matkul;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }

    public function dashboard_dosen_api()
    {
        $jumlah_matkul = Matkul::where('user_id', auth()->user()->id)->count();

        $dosen = Auth::user();
        $mahasiswa = [];

        $user = User::where('roles', 'mahasiswa')->get();

        foreach ($user as $item) {
            $penguji = json_decode($item->penguji, true);

            foreach ($penguji as $key => $value) {
                if ($dosen->id == $value['user_id']) {
                    $data_user = User::where('id', $item->id)->first();

                    $mahasiswa[] = $data_user;
                }
            }
        }

        $jumlah_mahasiswa = count($mahasiswa);

        $data_dosen['id'] = $dosen->id;
        $data_dosen['nama'] = $dosen->nama;
        $data_dosen['username'] = $dosen->username;
        $data_dosen['roles'] = $dosen->roles;

        $data['dosen'] = $data_dosen;
        $data['jumlah_matkul'] = $jumlah_matkul;
        $data['jumlah_mahasiswa'] = $jumlah_mahasiswa;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }

    public function get_pengujian_api()
    {
        $user = Auth::user();

        $penguji = json_decode($user->penguji, true);

        $data_lengkap_penguji = [];

        foreach ($penguji as $key => $value) {
            $data_user = User::find($value['user_id']);

            $matkul_user = Matkul::find($value['matkul_id']);

            $data_lengkap_penguji[] = [
                'user_id' => $value['user_id'],
                'nama' => $data_user->nama,
                'matkul_id' => $value['matkul_id'],
                'matkul_nama' => $matkul_user->nama,
            ];
        }

        $penguji = $data_lengkap_penguji;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $penguji
        ]);
    }

    public function dashboard_mahasiswa_api()
    {
        $user = Auth::user();

        $penguji = json_decode($user->penguji, true);

        $data_lengkap_penguji = [];

        foreach ($penguji as $key => $value) {
            $data_user = User::find($value['user_id']);

            $matkul_user = Matkul::find($value['matkul_id']);

            $data_lengkap_penguji[$key] = [
                'user_id' => $value['user_id'],
                'nama' => $data_user->nama,
                'matkul_id' => $value['matkul_id'],
                'matkul_nama' => $matkul_user->nama,
            ];
        }

        $penguji = $data_lengkap_penguji;

        $data_mahasiswa['id'] = $user->id;
        $data_mahasiswa['nama'] = $user->nama;
        $data_mahasiswa['username'] = $user->username;
        $data_mahasiswa['roles'] = $user->roles;

        $data['mahasiswa'] = $data_mahasiswa;
        $data['penguji'] = $penguji;

        $status_ujian = [];
        $data_penguji = json_decode($user->penguji);
        $data_nilai = json_decode($user->nilai);

        foreach ($penguji as $item) {
            if ($data_penguji->penguji_1->user_id == $item['user_id'] && $data_penguji->penguji_1->matkul_id == $item['matkul_id']) {
                $nilai_asli = $data_nilai->nilai_penguji_1->nilai_ujian;
                $remidial = $data_nilai->nilai_penguji_1->remidial;
                $nilai_remidial = $data_nilai->nilai_penguji_1->nilai_remidial;
                $sk = $data_nilai->nilai_penguji_1->sk;
            }
            if ($data_penguji->penguji_2->user_id == $item['user_id'] && $data_penguji->penguji_2->matkul_id == $item['matkul_id']) {
                $nilai_asli = $data_nilai->nilai_penguji_2->nilai_ujian;
                $remidial = $data_nilai->nilai_penguji_2->remidial;
                $nilai_remidial = $data_nilai->nilai_penguji_2->nilai_remidial;
                $sk = $data_nilai->nilai_penguji_2->sk;
            }
            if ($data_penguji->penguji_3->user_id == $item['user_id'] && $data_penguji->penguji_3->matkul_id == $item['matkul_id']) {
                $nilai_asli = $data_nilai->nilai_penguji_3->nilai_ujian;
                $remidial = $data_nilai->nilai_penguji_3->remidial;
                $nilai_remidial = $data_nilai->nilai_penguji_3->nilai_remidial;
                $sk = $data_nilai->nilai_penguji_3->sk;
            }

            if ($sk) {
                $status = "selesai";
            } elseif ($remidial) {
                if ($nilai_remidial !== null) {
                    $status = "selesai";
                } else {
                    $status = "remidial";
                }
            } elseif ($nilai_asli !== null) {
                $status = "selesai";
            } else {
                $status = "belum_ujian";
            }

            $status_ujian[] = [
                'matkul_id' => $item['matkul_id'],
                'matkul_nama' => $item['matkul_nama'],
                'progres' => $status,
            ];
        }

        $data['status'] = $status_ujian;

        return response()->json([
            'success' => true,
            'message' => 'Get Data Berhasil',
            'data' => $data
        ]);
    }
}
