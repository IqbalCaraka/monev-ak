<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Hash;

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

        while (($data = fgetcsv($file)) !== false) {
            // Skip jika data tidak lengkap
            if (count($data) < 3) {
                continue;
            }

            // Parse data CSV
            $row = explode(',', $data[0]);

            if (count($row) < 5) {
                continue;
            }

            $nip = trim($row[1]);
            $nama = trim($row[2]);
            $jabatan = trim($row[3]);
            $golongan = trim($row[4]);

            // Skip jika NIP kosong atau bukan angka
            if (empty($nip) || !is_numeric($nip)) {
                continue;
            }

            // Tentukan role_id berdasarkan jabatan
            $role_id = 2; // Default: Pegawai
            if (stripos($jabatan, 'Pimpinan') !== false || stripos($jabatan, 'Direktur') !== false) {
                $role_id = 3; // Pimpinan
            }

            Pegawai::create([
                'nip' => $nip,
                'nama' => $nama,
                'jabatan' => $jabatan,
                'golongan' => $golongan,
                'role_id' => $role_id,
                'email' => strtolower(str_replace(' ', '', $nama)) . '@anri.go.id',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]);
        }

        fclose($file);

        $this->command->info('Data pegawai berhasil di-seed!');
    }
}
