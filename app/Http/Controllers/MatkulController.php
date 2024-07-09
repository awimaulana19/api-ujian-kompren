<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Matkul;
use App\Models\Matakuliah;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class MatkulController extends Controller
{
    public function index()
    {
        $matkul = Matakuliah::get();

        return view('Admin.Matkul.index', compact('matkul'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
        ]);

        $matkul = new Matakuliah([
            'nama' => $request->nama,
        ]);
        $matkul->save();

        Alert::success('Sukses', 'Berhasil menambah data');

        return redirect('/admin/matkul');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
        ]);

        $matkul = Matakuliah::findOrFail($id);

        $data = $request->all();
        $matkul->update($data);

        Alert::success('Success', 'Berhasil mengupdate data');

        return redirect('/admin/matkul');
    }



    public function destroy($id)
    {
        $matkul = Matakuliah::find($id);

        $matkul->delete();
        Alert::success('Success', 'Berhasil menghapus data');
        return redirect('/admin/matkul');
    }
}
