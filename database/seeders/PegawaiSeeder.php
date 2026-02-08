<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pegawai;

class PegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = base_path('List Pegawai Dit.AK - Sheet1.csv');

        if (!file_exists($csvFile)) {
            $this->command->error('File CSV tidak ditemukan!');
            return;
        }

        $file = fopen($csvFile, 'r');
        $count = 0;

        while (($data = fgetcsv($file)) !== false) {
            // Data sudah di-parse oleh fgetcsv, langsung akses index
            if (count($data) < 5) {
                continue;
            }

            $nip = trim($data[1]);
            $nama = trim($data[2]);
            $jabatan = trim($data[3]);
            $golongan = isset($data[4]) ? trim($data[4]) : '';

            // Skip jika NIP kosong atau bukan angka
            if (empty($nip) || !is_numeric($nip)) {
                $this->command->warn("Skipping invalid NIP: $nip");
                continue;
            }

            // Tentukan role_id berdasarkan jabatan
            $role_id = 2; // Default: Pegawai
            if (stripos($jabatan, 'Pimpinan') !== false || stripos($jabatan, 'Direktur') !== false) {
                $role_id = 3; // Pimpinan
            }

            try {
                Pegawai::updateOrCreate(
                    ['nip' => $nip],
                    [
                        'nama' => $nama,
                        'jabatan' => $jabatan,
                        'golongan' => $golongan,
                        'role_id' => $role_id,
                        'is_active' => true,
                    ]
                );
                $count++;
                $this->command->info("Inserted: $nama ($nip)");
            } catch (\Exception $e) {
                $this->command->error("Error inserting $nama: " . $e->getMessage());
            }
        }

        fclose($file);

        $this->command->info("Total $count pegawai berhasil di-seed!");
    }
}
