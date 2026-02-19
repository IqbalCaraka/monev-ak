<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Instansi;

class InstansiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Coba beberapa kemungkinan nama file
        $possibleFiles = [
            'Instansi 190226.csv',
            'ref_instansi.csv',
        ];

        $csvFile = null;
        foreach ($possibleFiles as $fileName) {
            $path = base_path($fileName);
            if (file_exists($path)) {
                $csvFile = $path;
                $this->command->info("Menggunakan file: $fileName");
                break;
            }
        }

        if (!$csvFile) {
            $this->command->error('File CSV instansi tidak ditemukan!');
            return;
        }

        $file = fopen($csvFile, 'r');
        $count = 0;

        // Read header row
        $header = fgetcsv($file);

        while (($data = fgetcsv($file)) !== false) {
            // Skip jika data tidak lengkap (minimal harus ada id dan nama)
            if (count($data) < 3) {
                continue;
            }

            // CSV format: id, lokasi_id, nama, jenis, cepat_kode, proses_berkas_dipusat, mgr_cepat_kode,
            // cepat_kode5, ncsistime, status, cepat_kode5_lama, nama_baru, nama_jabatan, jenis_instansi_id, ...
            $id = trim($data[0]);
            $lokasiId = isset($data[1]) && $data[1] !== '\N' && $data[1] !== '' ? trim($data[1]) : null;
            $nama = trim($data[2]);
            $jenis = isset($data[3]) ? trim($data[3]) : null;
            $namaBaru = isset($data[11]) && $data[11] !== '\N' && $data[11] !== '' ? trim($data[11]) : null;
            $namaJabatan = isset($data[12]) && $data[12] !== '\N' && $data[12] !== '' ? trim($data[12]) : null;
            $jenisInstansiId = isset($data[13]) && $data[13] !== '\N' && $data[13] !== '' ? trim($data[13]) : null;
            $kantorRegionalId = isset($data[22]) && $data[22] !== '\N' && $data[22] !== '' ? trim($data[22]) : null;
            $provId = isset($data[25]) && $data[25] !== '\N' && $data[25] !== '' ? trim($data[25]) : null;

            // Skip jika ID atau nama kosong
            if (empty($id) || empty($nama)) {
                continue;
            }

            try {
                Instansi::updateOrCreate(
                    ['id' => $id],
                    [
                        'lokasi_id' => $lokasiId,
                        'nama' => $nama,
                        'jenis' => $jenis,
                        'nama_baru' => $namaBaru,
                        'nama_jabatan' => $namaJabatan,
                        'jenis_instansi_id' => $jenisInstansiId,
                        'kantor_regional_id' => $kantorRegionalId,
                        'prov_id' => $provId,
                    ]
                );
                $count++;
                if ($count <= 10 || $count % 100 == 0) {
                    $this->command->info("Inserted: $nama ($id)");
                }
            } catch (\Exception $e) {
                $this->command->error("Error inserting $nama: " . $e->getMessage());
            }
        }

        fclose($file);

        $this->command->info("Total $count instansi berhasil di-seed!");
    }
}
