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
        $csvFile = base_path('ref_instansi.csv');

        if (!file_exists($csvFile)) {
            $this->command->error('File CSV instansi tidak ditemukan!');
            return;
        }

        $file = fopen($csvFile, 'r');
        $count = 0;
        $header = true;

        while (($data = fgetcsv($file)) !== false) {
            // Skip header row
            if ($header) {
                $header = false;
                continue;
            }

            // Skip jika data tidak lengkap
            if (count($data) < 9) {
                continue;
            }

            $id = trim($data[0]);

            // Skip jika ID kosong
            if (empty($id)) {
                continue;
            }

            // Remove BOM if exists
            $id = str_replace("\xEF\xBB\xBF", '', $id);

            try {
                $kanreg = isset($data[7]) ? trim($data[7]) : null;
                $provId = isset($data[8]) ? trim($data[8]) : null;

                Instansi::updateOrCreate(
                    ['id' => $id],
                    [
                        'lokasi_id' => !empty($data[1]) ? trim($data[1]) : null,
                        'nama' => trim($data[2]),
                        'jenis' => !empty($data[3]) ? trim($data[3]) : null,
                        'nama_baru' => !empty($data[4]) ? trim($data[4]) : null,
                        'nama_jabatan' => !empty($data[5]) ? trim($data[5]) : null,
                        'jenis_instansi_id' => !empty($data[6]) ? trim($data[6]) : null,
                        'kantor_regional_id' => $kanreg !== null && $kanreg !== '' ? $kanreg : null,
                        'prov_id' => $provId !== null && $provId !== '' ? $provId : null,
                    ]
                );
                $count++;
                $this->command->info("Inserted: " . trim($data[2]) . " ($id)");
            } catch (\Exception $e) {
                $this->command->error("Error inserting " . trim($data[2]) . ": " . $e->getMessage());
            }
        }

        fclose($file);

        $this->command->info("Total $count instansi berhasil di-seed!");
    }
}
