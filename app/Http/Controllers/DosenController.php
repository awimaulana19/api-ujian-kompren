<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Matkul;
use GuzzleHttp\Client;
use App\Models\Matakuliah;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;

class DosenController extends Controller
{
    public function index()
    {
        $user = User::where('roles', 'dosen')->get();
        $matakuliah = Matakuliah::get();
        return view('Admin.Dosen.index', compact('user', 'matakuliah'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matakuliah_id' => 'required|array|min:1',
            'matakuliah_id.*' => 'integer|exists:matakuliahs,id',
        ]);

        if ($validator->fails()) {
            Alert::error('Gagal', 'Mata Kuliah Tidak Boleh Kosong');

            return redirect('/admin/dosen');
        }

        $validator2 = Validator::make($request->all(), [
            'wa' => 'required|unique:users,wa'
        ]);

        if ($validator2->fails()) {
            Alert::error('Gagal', 'Nomor WA tidak Boleh Sama');

            return redirect('/admin/dosen');
        }

        $already_username = User::where('username', $request->username)->first();

        if ($already_username) {
            Alert::error('Gagal', 'Usernname/Nip Sudah Ada');

            return redirect('/admin/dosen');
        }

        $hashedPassword = bcrypt($request->password);

        $user = new User([
            'nama' => $request->nama,
            'username' => $request->username,
            'wa' => $request->wa,
            'password' => $hashedPassword,
            'roles' => $request->roles,
            'is_verification' => $request->is_verification,
        ]);
        $user->save();

        foreach ($request->matakuliah_id as $item) {
            $matakuliah = Matakuliah::where('id', $item)->first();
            $matkul = new Matkul();

            $matkul->nama = $matakuliah->nama;
            $matkul->matakuliah_id = $matakuliah->id;
            $matkul->user_id = $user->id;
            $matkul->save();
        }

        Alert::success('Sukses', 'Berhasil menambah data');

        return redirect('/admin/dosen');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'matakuliah_id' => 'required|array|min:1',
            'matakuliah_id.*' => 'integer|exists:matakuliahs,id',
        ]);

        if ($validator->fails()) {
            Alert::error('Gagal', 'Mata Kuliah Tidak Boleh Kosong');

            return redirect('/admin/dosen');
        }

        $validator2 = Validator::make($request->all(), [
            'wa' => 'required|unique:users,wa,' . $id,
        ]);

        if ($validator2->fails()) {
            Alert::error('Gagal', 'Nomor WA tidak Boleh Sama');

            return redirect('/admin/dosen');
        }

        $user = User::findOrFail($id);
        $data = $request->all();

        if ($request->has('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        $matkuls_to_delete = Matkul::where('user_id', $id)
            ->whereNotIn('matakuliah_id', $request->matakuliah_id)
            ->get();

        foreach ($matkuls_to_delete as $matkul) {
            $matkul->delete();
        }

        foreach ($request->matakuliah_id as $item) {
            $matakuliah = Matakuliah::where('id', $item)->first();
            $matkul = Matkul::where('user_id', $id)->where('matakuliah_id', $item)->first();

            if (!$matkul) {
                $buat_matkul = new Matkul();
                $buat_matkul->nama = $matakuliah->nama;
                $buat_matkul->matakuliah_id = $matakuliah->id;
                $buat_matkul->user_id = $user->id;
                $buat_matkul->save();
            } else {
                $matkul->nama = $matakuliah->nama;
                $matkul->update();
            }
        }

        Alert::success('Success', 'Berhasil mengupdate data');
        return redirect('/admin/dosen');
    }

    public function destroy($id)
    {
        $user = User::find($id);

        $user->delete();
        Alert::success('Success', 'Berhasil menghapus akun');
        return redirect('/admin/dosen');
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
            'tanggal_sk' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Kirim SK Gagal',
                'data' => $validator->errors()
            ], 404);
        }

        $signature = $request->input('signature');
        $signature = str_replace('data:image/png;base64,', '', $signature);
        $signature = str_replace(' ', '+', $signature);
        $imageName = auth()->user()->username . '.png';
        File::put(public_path('signatures') . '/' . $imageName, base64_decode($signature));

        $user = User::where('username', $request->nim_mahasiswa)->first();
        $matkul = Matkul::where('id', $request->mata_kuliah_id)->first();
        $originalData = json_decode($user->nilai, true);

        $penguji = json_decode($user->penguji);

        if ($matkul->id == $penguji->penguji_1->matkul_id && $matkul->user_id == $penguji->penguji_1->user_id) {
            if ($originalData['nilai_penguji_1']['remidial']) {
                $originalData['nilai_penguji_1']['nilai_ujian'] = $request->nilai_angka;
            }
            $originalData['nilai_penguji_1']['sk'] = $request->tanggal_sk;
            $originalData['nilai_penguji_1']['keterangan'] = $request->keterangan;
            $tanggal_sk = $originalData['nilai_penguji_1']['sk'];
            $keterangan = $originalData['nilai_penguji_1']['keterangan'];
        }
        if ($matkul->id == $penguji->penguji_2->matkul_id && $matkul->user_id == $penguji->penguji_2->user_id) {
            if ($originalData['nilai_penguji_2']['remidial']) {
                $originalData['nilai_penguji_2']['nilai_ujian'] = $request->nilai_angka;
            }
            $originalData['nilai_penguji_2']['sk'] = $request->tanggal_sk;
            $originalData['nilai_penguji_2']['keterangan'] = $request->keterangan;
            $tanggal_sk = $originalData['nilai_penguji_2']['sk'];
            $keterangan = $originalData['nilai_penguji_2']['keterangan'];
        }
        if ($matkul->id == $penguji->penguji_3->matkul_id && $matkul->user_id == $penguji->penguji_3->user_id) {
            if ($originalData['nilai_penguji_3']['remidial']) {
                $originalData['nilai_penguji_3']['nilai_ujian'] = $request->nilai_angka;
            }
            $originalData['nilai_penguji_3']['sk'] = $request->tanggal_sk;
            $originalData['nilai_penguji_3']['keterangan'] = $request->keterangan;
            $tanggal_sk = $originalData['nilai_penguji_3']['sk'];
            $keterangan = $originalData['nilai_penguji_3']['keterangan'];
        }

        $updatedJson = json_encode($originalData);

        $user->nilai = $updatedJson;
        $user->update();

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

        $signaturePath = public_path('/signatures/' . auth()->user()->username . '.png');
        $signatureBase64 = null;

        if (File::exists($signaturePath)) {
            $fileContents = File::get($signaturePath);
            $signatureBase64 = base64_encode($fileContents);
            $decoded = base64_decode($signatureBase64, true);
            if ($decoded !== false && $decoded !== null && strlen($decoded) > 0) {
                $signaturePath = '/signatures/' . auth()->user()->username . '.png';
            } else {
                $signaturePath = null;
            }
        } else {
            $signaturePath = null;
        }

        $client = new Client();
        $url = "http://8.215.36.120:3000/message";

        $wa = $user->wa;
        $message = "SK Nilai Mata Kuliah " . $matkul->matakuliah->nama . " Anda Sudah Tersedia, Nilai Anda Adalah " . $request->nilai_angka;

        $body = [
            'phoneNumber' => $wa,
            'message' => $message,
        ];

        $client->request('POST', $url, [
            'form_params' => $body,
            'verify'  => false,
        ]);

        $pdf = PDF::loadView('Mahasiswa.SkPenilaian.skPDF', compact('request', 'tanggal_sk', 'keterangan', 'nilai_huruf', 'signaturePath'))->setPaper('A4', 'potrait')->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->render();

        return $pdf->stream("Surat Penilaian {$user->nama}.pdf");
    }

    public function batal_kirim_api($id, $user_id)
    {
        $matkul = Matkul::where('id', $id)->first();

        if (!$matkul) {
            return response()->json([
                'success' => false,
                'message' => 'Batal Kirim Gagal, Id Matkul Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        $user = User::where('id', $user_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Batal Kirim Gagal, Id User Tidak Ditemukan',
                'data' => null
            ], 404);
        }

        if (!$user->penguji) {
            return response()->json([
                'success' => false,
                'message' => 'Batal Kirim Gagal, Id User Bukan Mahasiswa',
                'data' => null
            ], 404);
        }

        $originalData = json_decode($user->nilai, true);

        $penguji = json_decode($user->penguji);

        if ($matkul->id == $penguji->penguji_1->matkul_id && $matkul->user_id == $penguji->penguji_1->user_id) {
            $originalData['nilai_penguji_1']['sk'] = null;
            $originalData['nilai_penguji_1']['keterangan'] = null;
            if ($originalData['nilai_penguji_1']['remidial']) {
                $originalData['nilai_penguji_1']['nilai_ujian'] = $originalData['nilai_penguji_1']['nilai_remidial'];
            }
        }
        if ($matkul->id == $penguji->penguji_2->matkul_id && $matkul->user_id == $penguji->penguji_2->user_id) {
            $originalData['nilai_penguji_2']['sk'] = null;
            $originalData['nilai_penguji_2']['keterangan'] = null;
            if ($originalData['nilai_penguji_2']['remidial']) {
                $originalData['nilai_penguji_2']['nilai_ujian'] = $originalData['nilai_penguji_2']['nilai_remidial'];
            }
        }
        if ($matkul->id == $penguji->penguji_3->matkul_id && $matkul->user_id == $penguji->penguji_3->user_id) {
            $originalData['nilai_penguji_3']['sk'] = null;
            $originalData['nilai_penguji_3']['keterangan'] = null;
            if ($originalData['nilai_penguji_3']['remidial']) {
                $originalData['nilai_penguji_3']['nilai_ujian'] = $originalData['nilai_penguji_3']['nilai_remidial'];
            }
        }

        $updatedJson = json_encode($originalData);

        $user->nilai = $updatedJson;
        $user->update();

        $user->makeHidden(['penguji', 'sk_kompren',  'is_verification', 'created_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'message' => 'Batal Kirim Berhasil',
            'data' => $user
        ]);
    }
}
