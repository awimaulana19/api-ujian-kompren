<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Penilaian</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
        }

        .header {
            display: flex;
            align-items: center;
        }

        .logo {
            width: 20%;
            /* Sesuaikan lebar logo */
        }

        .kampus {
            width: 80%;
            text-align: center;
            /* padding: 0px 20px; */
        }

        .penguji {
            display: flex;
        }

        .penguji .label {
            width: 50%;
            margin-bottom: 0px;
        }

        .penguji .isi {
            width: 50%;
            margin-bottom: 0px;
        }


        .garis {
            border-bottom: 2px solid #000;
            margin: 10px 0;
        }

        .keterangan {
            margin-top: 20px;
            text-align: center;
        }


        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
            text-align: left;
        }

        th,
        td {
            padding: 10px;
        }

        .nilai-subcol {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            /* Baris baru jika tidak cukup lebar */
        }

        .rowttd {
            display: flex;
        }

        .col_ttd8 {
            width: 60%;
        }

        .col_ttd4 {
            width: 40%;
            margin-top: 60px;
            margin-left: auto;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <table width="100%" style="border:0;">
        <tr style="border:0;">
            <td width="16%" style="border:0;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/assets2/img/logo_uin.png'))) }}"
                    width="100%">
            </td>
            <td width="84%" style="text-align: center; border:0;">
                <h4 style="margin: 0;">KEMENTERIAN AGAMA R.I</h4>
                <h4 style="margin: 0;">UNIVERSITAS ISLAM NEGERI ALAUDDIN MAKASSAR</h4>
                <h4 style="margin: 0;">FAKULTAS SAINS DAN TEKNOLOGI</h4>
                <h4 style="margin: 0;">JURUSAN SISTEM INFORMASI</h4>
                <p style="margin: 0;">Kampus I : Jln. Sultan Alauddin No. 63 Telp. 864924 (Fax 864923) Makassar</p>
                <p style="margin: 0;">Kampus II : Jln. HM. Yasin Limpo No.36 Romang Polong - Gowa Telp. (0411) 8417879
                </p>
            </td>
        </tr>
    </table>

    <div class="garis"></div>

    <div class="keterangan">
        <h4>DAFTAR NILAI</h4>
        <h4 style="margin-bottom: 30px;">UJIAN AKHIR PROGRAM STUDI / KOMPREHENSHIP</h4>
    </div>

    <table width="100%" style="border:0;">
        <tr style="border:0;">
            <td width="30%" style="border:0;">
                <h5 class="label">DOSEN PENGUJI</h5>
                <h5 class="label">MATA KULIAH</h5>
            </td>
            <td width="70%" style="border:0;">
                <h5 class="isi">: {{ $request->dosen_penguji }}</h5>
                <h5 class="isi">: {{ $request->mata_kuliah }}</h5>
            </td>
        </tr>
    </table>


    <table>
        <thead>
            <tr>
                <th style="text-align: center;" rowspan="2">Nama/NIM</th>
                <th style="text-align: center;" colspan="2">Nilai</th>
                <th style="text-align: center;" rowspan="2">Keterangan</th>
            </tr>
            <tr>
                <th style="text-align: center;">Huruf</th>
                <th style="text-align: center;">Angka</th>
            </tr>
        </thead>
        <tbody>
            <!-- Isi tabel sesuai kebutuhan -->
            <tr>
                <td style="text-align: center">{{ $request->nama_mahasiswa }} / {{ $request->nim_mahasiswa }}</td>
                <td style="text-align: center">{{ $nilai_huruf }}</td>
                <td style="text-align: center">{{ $request->nilai_angka }}</td>
                <td style="text-align: center">{{ $keterangan }}</td>
            </tr>
            <!-- Tambahkan baris lain sesuai kebutuhan -->
        </tbody>
    </table>

    <div class="row_ttd">
        <div class="col_ttd8"></div>
        <div class="col_ttd4">
            <p>Romangpolong, {{ $tanggal_sk }}</p>
            <p>Penguji,</p>
            <p style="padding-top: 80px;">{{ $request->dosen_penguji }}</p>
        </div>
    </div>

</body>

</html>
