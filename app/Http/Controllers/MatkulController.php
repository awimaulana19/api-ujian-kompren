<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Matkul;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class MatkulController extends Controller
{
    public function index()
    {
        $matkul = Matkul::get();
        $dosen = User::where('roles', 'dosen')->get();

        return view('Admin.Matkul.index', compact('matkul', 'dosen'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'user_id' => 'required',
        ]);

        $matkul = new Matkul([
            'nama' => $request->nama,
            'user_id' => $request->user_id,
        ]);
        $matkul->save();

        Alert::success('Sukses', 'Berhasil menambah data');

        return redirect('/admin/matkul');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'user_id' => 'required',
        ]);
        
        $matkul = Matkul::findOrFail($id);

        $data = $request->all();
        $matkul->update($data);

        Alert::success('Success', 'Berhasil mengupdate data');

        return redirect('/admin/matkul');
    }



    public function destroy($id)
    {
        $matkul = Matkul::find($id);

        $matkul->delete();
        Alert::success('Success', 'Berhasil menghapus data');
        return redirect('/admin/matkul');
    }
}
