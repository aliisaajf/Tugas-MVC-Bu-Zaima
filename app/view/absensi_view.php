<?php
// 1. KONEKSI KE DATABASE PHPMYADMIN
$koneksi = mysqli_connect("localhost", "root", "", "db_absensi_mvc");

if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Variabel untuk menyimpan inputan agar tidak hilang saat tombol ditekan
$mapel_val = '';
$kelas_val = '';
$guru_val  = '';
$bulan_val = '';
$tahun_val = '';
$data_absensi = [];
$mode_tampil = false;

// 2. PROSES SAAT TOMBOL DIKLIK (BISA SIMPAN ATAU TAMPILKAN)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mata_pelajaran = mysqli_real_escape_string($koneksi, $_POST['mata_pelajaran'] ?? '');
    $kelas          = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
    $nama_guru      = mysqli_real_escape_string($koneksi, $_POST['nama_guru'] ?? '');
    $bulan          = mysqli_real_escape_string($koneksi, $_POST['bulan'] ?? '');
    $tahun          = mysqli_real_escape_string($koneksi, $_POST['tahun'] ?? ''); 
    
    // Simpan nilai ke variabel agar form tidak mereset jadi kosong
    $mapel_val = $mata_pelajaran;
    $kelas_val = $kelas;
    $guru_val  = $nama_guru;
    $bulan_val = $bulan;
    $tahun_val = $tahun;

    // A. JIKA TOMBOL "SIMPAN" DIKLIK
    if (isset($_POST['simpan']) && isset($_POST['nama_murid'])) {
        $daftar_nama = $_POST['nama_murid'];
        $kehadiran_all = $_POST['kehadiran'] ?? [];

        foreach ($daftar_nama as $index_murid => $nama) {
            $nama_clean = mysqli_real_escape_string($koneksi, trim($nama));
            
            if (!empty($nama_clean)) {
                // Cek murid di database
                $cek_murid = mysqli_query($koneksi, "SELECT id_murid FROM murid WHERE nama_murid = '$nama_clean'");
                if (mysqli_num_rows($cek_murid) > 0) {
                    $row_murid = mysqli_fetch_assoc($cek_murid);
                    $id_murid  = $row_murid['id_murid'];
                } else {
                    mysqli_query($koneksi, "INSERT INTO murid (nama_murid) VALUES ('$nama_clean')");
                    $id_murid = mysqli_insert_id($koneksi);
                }

                // Ambil data kehadiran untuk murid ini berdasarkan index barisnya
                $kehadiran_murid = $kehadiran_all[$index_murid] ?? [];

                // Looping 30 hari
                for ($tanggal = 1; $tanggal <= 30; $tanggal++) {
                    $status = isset($kehadiran_murid[$tanggal]) ? mysqli_real_escape_string($koneksi, $kehadiran_murid[$tanggal]) : '-';

                    if ($status != '-') {
                        $cek_absen = mysqli_query($koneksi, "SELECT id_absensi FROM absensi WHERE id_murid = '$id_murid' AND bulan = '$bulan' AND tahun = '$tahun' AND tanggal = '$tanggal' AND mata_pelajaran = '$mata_pelajaran'");
                        
                        if (mysqli_num_rows($cek_absen) > 0) {
                            mysqli_query($koneksi, "UPDATE absensi SET status_kehadiran = '$status', kelas = '$kelas', nama_guru = '$nama_guru' WHERE id_murid = '$id_murid' AND bulan = '$bulan' AND tahun = '$tahun' AND tanggal = '$tanggal' AND mata_pelajaran = '$mata_pelajaran'");
                        } else {
                            mysqli_query($koneksi, "INSERT INTO absensi (id_murid, mata_pelajaran, kelas, nama_guru, bulan, tahun, tanggal, status_kehadiran) 
                                                VALUES ('$id_murid', '$mata_pelajaran', '$kelas', '$nama_guru', '$bulan', '$tahun', '$tanggal', '$status')");
                        }
                    }
                }
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=sukses");
        exit;
    } 
    
    // B. JIKA TOMBOL "TAMPILKAN DATA" DIKLIK
    elseif (isset($_POST['tampilkan'])) {
        $mode_tampil = true;
        
        $query_murid = mysqli_query($koneksi, "
            SELECT DISTINCT m.id_murid, m.nama_murid 
            FROM murid m
            JOIN absensi a ON m.id_murid = a.id_murid
            WHERE a.mata_pelajaran = '$mata_pelajaran' 
              AND a.kelas = '$kelas' 
              AND a.bulan = '$bulan' 
              AND a.tahun = '$tahun'
        ");
        
        while ($row = mysqli_fetch_assoc($query_murid)) {
            $id_murid = $row['id_murid'];
            $nama_murid = $row['nama_murid'];
            
            $kehadiran_murid = array_fill(1, 30, '-'); 
            
            $query_absen = mysqli_query($koneksi, "
                SELECT tanggal, status_kehadiran 
                FROM absensi 
                WHERE id_murid = '$id_murid' 
                  AND mata_pelajaran = '$mata_pelajaran' 
                  AND bulan = '$bulan' 
                  AND tahun = '$tahun'
            ");
            
            while ($absen_row = mysqli_fetch_assoc($query_absen)) {
                $kehadiran_murid[$absen_row['tanggal']] = $absen_row['status_kehadiran'];
            }
            
            $data_absensi[] = [
                'nama_murid' => $nama_murid,
                'kehadiran' => $kehadiran_murid
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas View Absensi MVC</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            font-size: 14px; 
        }
        h2.judul-halaman {
            color: #003366;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .header-info { 
            background-color: #f4f8fc; 
            border: 2px solid #003366; 
            border-radius: 8px; 
            padding: 20px 30px; 
            margin-bottom: 25px; 
            display: inline-block; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.05); 
        }
        .header-info div { 
            margin-bottom: 12px; 
        }
        .header-info label { 
            display: inline-block; 
            width: 120px; 
            font-weight: bold; 
        }
        .header-info input[type="text"], .header-info select {
            padding: 5px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        table { 
            border-collapse: collapse; 
            width: 100%;
        }
        th, td { 
            border: 1px solid #ffffff; 
            padding: 5px; 
            text-align: center; 
        }
        th { 
            background-color: #003366; 
            color: white; 
        }
        tbody tr:nth-child(even) { background-color: #e6eff7; }
        tbody tr:nth-child(odd) { background-color: #f0f4f8; }
        tbody tr:hover { background-color: #ccddec; }
        .nama-murid { text-align: left; min-width: 150px; } 
        select { padding: 2px; font-size: 12px; background: transparent; }
        .input-nama { width: 90%; border: none; outline: none; background: transparent; }
        .aksi-tombol { margin-top: 25px; }
        .btn {
            padding: 8px 18px;
            font-size: 14px;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            transition: 0.3s;
            text-decoration: none; 
            display: inline-block;
        }
        .btn-tambah, .btn-simpan, .btn-tampil, .btn-reset {
            background-color: #003366; 
            color: white;
            box-shadow: 0 2px 5px rgba(0, 51, 102, 0.4);
        }
        .btn-tambah:hover, .btn-simpan:hover, .btn-tampil:hover, .btn-reset:hover {
            background-color: #001a33; 
        }
        .btn-hapus {
            color: red;
            font-weight: bold;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-hapus:hover { color: darkred; }
    </style>
</head>
<body>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
        <script>alert("Data berhasil di simpan!");</script>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
        <h2 class="judul-halaman">Daftar Hadir Murid</h2>

        <div class="header-info">
            <div><label>Mata Pelajaran</label> : <input type="text" name="mata_pelajaran" value="<?php echo htmlspecialchars($mapel_val); ?>" required></div>
            <div><label>Kelas</label> : <input type="text" name="kelas" value="<?php echo htmlspecialchars($kelas_val); ?>" required></div>
            <div><label>Nama Guru</label> : <input type="text" name="nama_guru" value="<?php echo htmlspecialchars($guru_val); ?>" required></div>
            
            <div>
                <label>Bulan</label> : 
                <select name="bulan" required>
                    <option value="" disabled <?php if($bulan_val == '') echo 'selected'; ?>>- Pilih Bulan -</option>
                    <?php 
                    $list_bulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                    foreach($list_bulan as $b) {
                        $selected = ($bulan_val == $b) ? "selected" : "";
                        echo "<option value='$b' $selected>$b</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label>Tahun</label> : 
                <select name="tahun" required>
                    <option value="" disabled <?php if($tahun_val == '') echo 'selected'; ?>>- Pilih Tahun -</option>
                    <?php 
                    $tahun_sekarang = date("Y"); 
                    for($t = $tahun_sekarang - 2; $t <= $tahun_sekarang + 3; $t++) {
                        $selected = ($tahun_val == $t) ? "selected" : "";
                        echo "<option value='$t' $selected>$t</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div style="margin-top: 15px; text-align: right;">
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-reset">Isi Data Baru</a>
                <button type="submit" name="tampilkan" class="btn btn-tampil">Tampilkan Data</button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Nama Murid</th>
                    <th colspan="30">Tanggal</th>
                    <th rowspan="2">Aksi</th>
                </tr>
                <tr>
                    <?php for($i = 1; $i <= 30; $i++) { echo "<th>$i</th>"; } ?>
                </tr>
            </thead>
            <tbody id="badan-tabel">
                <?php
                if ($mode_tampil && count($data_absensi) > 0) {
                    $no_index = 0;
                    foreach ($data_absensi as $data) {
                        echo "<tr>";
                        echo "<td>" . ($no_index + 1) . "</td>";
                        
                        echo "<td class='nama-murid'>
                                <input type='text' class='input-nama' name='nama_murid[]' value='" . htmlspecialchars($data['nama_murid']) . "'>
                              </td>";
                        
                        for ($hari = 1; $hari <= 30; $hari++) {
                            $status_hadir = $data['kehadiran'][$hari];
                            echo "<td>
                                    <select name='kehadiran[$no_index][$hari]'>";
                            
                            $opsi = ['-', 'Hadir', 'Izin', 'Sakit', 'Alpha'];
                            foreach($opsi as $op) {
                                $selected = ($status_hadir == $op) ? "selected" : "";
                                echo "<option value='$op' $selected>$op</option>";
                            }
                            
                            echo "</select>
                                  </td>";
                        }
                        echo "<td>
                                <button type='button' class='btn-hapus' onclick='hapusBaris(this)'>X</button>
                              </td>";
                        echo "</tr>";
                        $no_index++;
                    }
                } 
                else {
                    if ($mode_tampil) {
                        echo "<script>alert('Data absensi tidak ditemukan untuk bulan tersebut!');</script>";
                    }
                    
                    for($no_index = 0; $no_index < 3; $no_index++) {
                        echo "<tr>";
                        echo "<td>" . ($no_index + 1) . "</td>";
                        
                        echo "<td class='nama-murid'>
                                <input type='text' class='input-nama' name='nama_murid[]' placeholder=''>
                              </td>";
                        
                        for($hari = 1; $hari <= 30; $hari++) {
                            echo "<td>
                                    <select name='kehadiran[$no_index][$hari]'>
                                        <option value='-'>-</option>
                                        <option value='Hadir'>Hadir</option>
                                        <option value='Izin'>Izin</option>
                                        <option value='Sakit'>Sakit</option>
                                        <option value='Alpha'>Alpha</option>
                                    </select>
                                  </td>";
                        }
                        echo "<td>
                                <button type='button' class='btn-hapus' onclick='hapusBaris(this)'>Hapus</button>
                              </td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>

        <div class="aksi-tombol">
            <button type="button" class="btn btn-tambah" onclick="tambahBaris()">+ Tambah Baris</button>
            <button type="submit" name="simpan" class="btn btn-simpan">Simpan</button>
        </div>
    </form>

    <script>
        function tambahBaris() {
            var tabel = document.getElementById("badan-tabel");
            var rowCount = tabel.rows.length;
            var barisBaru = tabel.insertRow(-1); 
            
            var selNo = barisBaru.insertCell(0);
            selNo.style.textAlign = "center";
            selNo.innerHTML = rowCount + 1;
            
            var selNama = barisBaru.insertCell(1);
            selNama.className = "nama-murid";
            selNama.innerHTML = "<input type='text' class='input-nama' name='nama_murid[]' placeholder=''>";
            
            for (var i = 1; i <= 30; i++) {
                var selAbsen = barisBaru.insertCell(i + 1);
                selAbsen.innerHTML = "<select name='kehadiran[" + rowCount + "][" + i + "]'><option value='-'>-</option><option value='Hadir'>Hadir</option><option value='Izin'>Izin</option><option value='Sakit'>Sakit</option><option value='Alpha'>Alpha</option></select>";
                selAbsen.style.textAlign = "center";
            }

            var selAksi = barisBaru.insertCell(32);
            selAksi.innerHTML = "<button type='button' class='btn-hapus' onclick='hapusBaris(this)'>Hapus</button>";
            selAksi.style.textAlign = "center";

            updateIndeksNamaDanSelect();
        }

        function hapusBaris(tombol) {
            var baris = tombol.parentNode.parentNode;
            baris.parentNode.removeChild(baris);
            updateIndeksNamaDanSelect();
        }

        function updateIndeksNamaDanSelect() {
            var tabel = document.getElementById("badan-tabel");
            for (var i = 0; i < tabel.rows.length; i++) {
                tabel.rows[i].cells[0].innerHTML = i + 1;
                
                // Update indeks name select option agar sinkron ulang dengan baris barunya
                var selects = tabel.rows[i].getElementsByTagName('select');
                for (var j = 0; j < selects.length; j++) {
                    var hari = j + 1;
                    selects[j].name = "kehadiran[" + i + "][" + hari + "]";
                }
            }
        }
    </script>

</body>
</html>