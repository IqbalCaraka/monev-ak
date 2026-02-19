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
        // Coba beberapa kemungkinan nama file
        $possibleFiles = [
            'List Pegawai Dit.AK - Sheet1 (1).csv',
            'List Pegawai Dit.AK - Sheet1.csv',
        ];

        $csvFile = null;
        foreach ($possibleFiles as $file) {
            $path = base_path($file);
            if (file_exists($path)) {
                $csvFile = $path;
                $this->command->info("Menggunakan file: $file");
                break;
            }
        }

        if (!$csvFile) {
            $this->command->warn('File CSV tidak ditemukan! Membuat data dummy...');
            $this->createDummyData();
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

            // Set default golongan jika kosong
            if (empty($golongan)) {
                $golongan = 'II/c';
                $this->command->warn("Setting default golongan II/c for: $nama");
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

    private function createDummyData(): void
    {
        $dummyPegawai = [
            ['nip' => '199001012015011001', 'nama' => 'Admin Sistem', 'jabatan' => 'Administrator', 'golongan' => 'III/d', 'role_id' => 1],
            ['nip' => '199002012015012001', 'nama' => 'John Doe', 'jabatan' => 'Staff Pelaksana', 'golongan' => 'III/a', 'role_id' => 2],
            ['nip' => '199003012015013001', 'nama' => 'Jane Smith', 'jabatan' => 'Staff Pelaksana', 'golongan' => 'III/b', 'role_id' => 2],
            ['nip' => '198901012015014001', 'nama' => 'Direktur Utama', 'jabatan' => 'Direktur', 'golongan' => 'IV/c', 'role_id' => 3],
            ['nip' => '198902012015015001', 'nama' => 'Pimpinan Cabang', 'jabatan' => 'Pimpinan', 'golongan' => 'IV/a', 'role_id' => 3],
        ];

        $count = 0;
        foreach ($dummyPegawai as $pegawai) {
            try {
                Pegawai::updateOrCreate(
                    ['nip' => $pegawai['nip']],
                    [
                        'nama' => $pegawai['nama'],
                        'jabatan' => $pegawai['jabatan'],
                        'golongan' => $pegawai['golongan'],
                        'role_id' => $pegawai['role_id'],
                        'is_active' => true,
                    ]
                );
                $count++;
                $this->command->info("Inserted dummy: {$pegawai['nama']} ({$pegawai['nip']})");
            } catch (\Exception $e) {
                $this->command->error("Error inserting {$pegawai['nama']}: " . $e->getMessage());
            }
        }

        $this->command->info("Total $count pegawai dummy berhasil di-seed!");
    }
}
