<?php

use App\Models\Matkul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\MatkulController;
use App\Http\Controllers\JawabanController;
use App\Http\Controllers\MahasiswaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AuthController::class, 'beranda']);

Route::get('/login', [AuthController::class, 'halaman_login'])->name('login');
Route::post('/login', [AuthController::class, 'login_action'])->name('login.action');

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::group(['middleware' => ['auth', 'OnlyAdmin']], function () {
    Route::get('/dashboard-admin', [AuthController::class, 'dashboard_admin']);

    // mahasiswa
    Route::get('/admin/mahasiswa/belum', [MahasiswaController::class, 'index']);
    Route::get('/admin/mahasiswa/telah', [MahasiswaController::class, 'telah']);
    Route::get('/admin/mahasiswa/tolak', [MahasiswaController::class, 'tolak']);
    Route::get('/admin/mahasiswa/nilai-ujian', [MahasiswaController::class, 'nilai_ujian']);
    Route::post('/admin/mahasiswa/update/{id}', [MahasiswaController::class, 'update']);
    Route::get('/admin/mahasiswa/delete/{id}', [MahasiswaController::class, 'destroy']);

    Route::get('/matkul-list', function (Request $request) {
        $matkul = Matkul::where('user_id', $request->penguji)->with('matakuliah')->get();

        return response()->json($matkul);
    });

    // dosen
    Route::get('/admin/dosen', [DosenController::class, 'index']);
    Route::post('/admin/dosen', [DosenController::class, 'store']);
    Route::post('/admin/dosen/update/{id}', [DosenController::class, 'update']);
    Route::get('/admin/dosen/delete/{id}', [DosenController::class, 'destroy']);
    Route::get('/admin/dosen/mahasiswa-diuji/{dosen}/{id}', [DosenController::class, 'lihat_mahasiswa_diuji']);
    Route::get('/admin/dosen/bank-soal/{id}', [DosenController::class, 'lihat_bank_soal']);
    Route::get('/admin/dosen/bank-soal/edit/{id}', [DosenController::class, 'edit_bank_soal']);
    Route::post('/admin/dosen/bank-soal/edit/{id}', [DosenController::class, 'update_bank_soal']);

    // matkul
    Route::get('/admin/matkul', [MatkulController::class, 'index']);
    Route::post('/admin/matkul', [MatkulController::class, 'store']);
    Route::post('/admin/matkul/update/{id}', [MatkulController::class, 'update']);
    Route::get('/admin/matkul/delete/{id}', [MatkulController::class, 'destroy']);
});
