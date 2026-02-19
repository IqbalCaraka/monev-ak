<?php

namespace App\Services;

class DmsScoreCalculator
{
    /**
     * Hitung skor arsip dari status_arsip JSON
     * Berdasarkan logika dari perhitungan_skor_arsip.html
     *
     * @param array $statusArsip
     * @param string $statusCpnsPns ('P' atau 'C')
     * @return array
     */
    public static function hitungSkor(array $statusArsip, string $statusCpnsPns): array
    {
        $skorUtama = 0;
        $detail = [
            'drh' => 7.5,
            'd2nip' => 0,
            'cpns' => 0,
            'pns' => 0,
            'pendidikan' => ['lengkap' => 0, 'total' => 0, 'skor' => 0],
            'golongan' => ['lengkap' => 0, 'total' => 0, 'skor' => 0],
            'jabatan' => ['lengkap' => 0, 'total' => 0, 'skor' => 0],
            'diklat' => ['lengkap' => 0, 'total' => 0, 'skor' => 0],
            'kondisional' => ['lengkap' => 0, 'total' => 0],
        ];

        // 1. DRH (Fixed 7.5)
        $skorUtama += 7.5;

        // 2. Data Utama
        if (isset($statusArsip['data_utama'])) {
            // D2NIP
            if (isset($statusArsip['data_utama']['d2np']) && $statusArsip['data_utama']['d2np'] == 1) {
                $detail['d2nip'] = 7.5;
                $skorUtama += 7.5;
            }

            // SK CPNS
            if (isset($statusArsip['data_utama']['cpns']) && $statusArsip['data_utama']['cpns'] == 1) {
                $detail['cpns'] = 7.5;
                $skorUtama += 7.5;
            }

            // SK PNS (untuk CPNS tetap dihitung 7.5 meski file PNS = 0)
            if ($statusCpnsPns === 'C' || (isset($statusArsip['data_utama']['pns']) && $statusArsip['data_utama']['pns'] == 1)) {
                $detail['pns'] = 7.5;
                $skorUtama += 7.5;
            }
        }

        // 3. Pendidikan
        $skorUtama += self::hitungRiwayat($statusArsip, 'pendidikan', $detail);

        // 4. Golongan
        $skorUtama += self::hitungRiwayat($statusArsip, 'golongan', $detail);

        // 5. Jabatan
        $skorUtama += self::hitungRiwayat($statusArsip, 'jabatan', $detail);

        // 6. Diklat
        $skorUtama += self::hitungRiwayat($statusArsip, 'diklat', $detail);

        // 7. ARSIP KONDISIONAL (Maksimal 10 poin)
        $jenisKondisional = ['angka_kredit', 'pindah_instansi', 'pmk', 'penghargaan', 'cltn', 'skp22'];
        $totalRiwayat = 0;
        $totalLengkap = 0;

        foreach ($jenisKondisional as $jenis) {
            if (isset($statusArsip[$jenis]) && is_array($statusArsip[$jenis])) {
                $items = $statusArsip[$jenis];
                $total = count($items);
                $lengkap = count(array_filter($items, fn($v) => $v == 1));

                $totalRiwayat += $total;
                $totalLengkap += $lengkap;
            }
        }

        $detail['kondisional']['total'] = $totalRiwayat;
        $detail['kondisional']['lengkap'] = $totalLengkap;

        $skorKondisional = 0;
        $adaKondisional = $totalRiwayat > 0;

        if ($adaKondisional) {
            // Ada riwayat kondisional: hitung proporsi
            $skorKondisional = ($totalLengkap / $totalRiwayat) * 10;
        } else {
            // BONUS +10 jika tidak punya riwayat kondisional
            $skorKondisional = 10;
        }

        $skorFinal = $skorUtama + $skorKondisional;
        $kategori = $skorFinal >= 90 ? 'Lengkap' : 'Tidak Lengkap';

        return [
            'skor_utama' => round($skorUtama, 2),
            'skor_kondisional' => round($skorKondisional, 2),
            'skor_final' => round($skorFinal, 2),
            'kategori' => $kategori,
            'detail' => $detail,
            'ada_kondisional' => $adaKondisional,
        ];
    }

    /**
     * Hitung skor untuk riwayat (pendidikan, golongan, jabatan, diklat)
     * Maksimal 15 poin per jenis
     *
     * @param array $statusArsip
     * @param string $jenis
     * @param array &$detail (by reference)
     * @return float
     */
    private static function hitungRiwayat(array $statusArsip, string $jenis, array &$detail): float
    {
        if (isset($statusArsip[$jenis]) && is_array($statusArsip[$jenis])) {
            $items = $statusArsip[$jenis];
            $total = count($items);
            $lengkap = count(array_filter($items, fn($v) => $v == 1));

            $detail[$jenis] = [
                'lengkap' => $lengkap,
                'total' => $total,
                'skor' => $total === 0 ? 15 : ($lengkap / $total) * 15,
            ];

            return $detail[$jenis]['skor'];
        } else {
            // Jika tidak punya riwayat sama sekali, dianggap lengkap (15 poin)
            $detail[$jenis] = [
                'lengkap' => 0,
                'total' => 0,
                'skor' => 15,
            ];

            return 15;
        }
    }

    /**
     * Tentukan status kelengkapan berdasarkan skor CSV
     *
     * Ketentuan:
     * - >90: Sangat Lengkap
     * - 55.6 - 90: Lengkap
     * - 30 - 55.5: Cukup Lengkap
     * - <30: Kurang Lengkap
     *
     * @param float $skorCsv
     * @return string
     */
    public static function tentukanStatusKelengkapan(?float $skorCsv): string
    {
        if ($skorCsv === null) {
            return 'Tidak Ada Data';
        }

        if ($skorCsv > 90) {
            return 'Sangat Lengkap';
        } elseif ($skorCsv >= 55.6) {
            return 'Lengkap';
        } elseif ($skorCsv >= 30) {
            return 'Cukup Lengkap';
        } else {
            return 'Kurang Lengkap';
        }
    }
}
