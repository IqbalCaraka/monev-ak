<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FillDayAndWorkCategory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:fill-day-work-category {--batch=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill day_name and work_category for existing log_aktivitas data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fill day_name and work_category...');

        $batchSize = (int) $this->option('batch');
        $totalRecords = DB::table('log_aktivitas')->whereNull('day_name')->count();

        if ($totalRecords === 0) {
            $this->info('No records to update. All records already have day_name and work_category.');
            return 0;
        }

        $this->info("Found {$totalRecords} records to update.");
        $this->info("Processing in batches of {$batchSize}...");

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;

        // Process in batches to avoid memory issues
        DB::table('log_aktivitas')
            ->whereNull('day_name')
            ->orderBy('id')
            ->chunk($batchSize, function ($records) use (&$processed, $bar) {
                $updates = [];

                foreach ($records as $record) {
                    // Use created_at_log (actual activity date), not created_at (Laravel timestamp)
                    $dateField = $record->created_at_log ?? $record->created_at;

                    if ($dateField) {
                        try {
                            $date = Carbon::parse($dateField);
                            $dayName = $this->getDayName($date->dayOfWeek);
                            $workCategory = $this->getWorkCategory($dayName);

                            $updates[] = [
                                'id' => $record->id,
                                'day_name' => $dayName,
                                'work_category' => $workCategory
                            ];
                        } catch (\Exception $e) {
                            // Skip if date parsing fails
                            continue;
                        }
                    }
                }

                // Batch update using CASE WHEN
                if (!empty($updates)) {
                    $this->batchUpdateRecords($updates);
                }

                $processed += count($records);
                $bar->advance(count($records));
            });

        $bar->finish();
        $this->newLine(2);
        $this->info("Successfully updated {$processed} records!");

        // Show summary
        $summary = DB::table('log_aktivitas')
            ->select('work_category', DB::raw('COUNT(*) as total'))
            ->whereNotNull('work_category')
            ->groupBy('work_category')
            ->get();

        $this->newLine();
        $this->info('Summary by work category:');
        $this->table(['Work Category', 'Total'], $summary->map(function($item) {
            return [$item->work_category, number_format($item->total)];
        }));

        return 0;
    }

    /**
     * Get day name in Indonesian from day of week number
     * 0 = Sunday, 1 = Monday, etc.
     */
    private function getDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];

        return $days[$dayOfWeek] ?? 'Unknown';
    }

    /**
     * Get work category based on day name
     * WFA: Senin, Rabu
     * WFO: Selasa, Kamis, Jumat
     * Libur: Sabtu, Minggu
     */
    private function getWorkCategory(string $dayName): string
    {
        $wfa = ['Senin', 'Rabu'];
        $wfo = ['Selasa', 'Kamis', 'Jumat'];
        $libur = ['Sabtu', 'Minggu'];

        if (in_array($dayName, $wfa)) {
            return 'WFA';
        } elseif (in_array($dayName, $wfo)) {
            return 'WFO';
        } elseif (in_array($dayName, $libur)) {
            return 'Libur';
        }

        return 'Unknown';
    }

    /**
     * Batch update records using raw SQL for performance
     */
    private function batchUpdateRecords(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        // Build CASE statements
        $caseDay = 'CASE id ';
        $caseWork = 'CASE id ';
        $ids = [];

        foreach ($updates as $update) {
            $id = DB::connection()->getPdo()->quote($update['id']);
            $dayName = DB::connection()->getPdo()->quote($update['day_name']);
            $workCategory = DB::connection()->getPdo()->quote($update['work_category']);

            $caseDay .= "WHEN {$id} THEN {$dayName} ";
            $caseWork .= "WHEN {$id} THEN {$workCategory} ";
            $ids[] = $id;
        }

        $caseDay .= 'END';
        $caseWork .= 'END';
        $idList = implode(',', $ids);

        $sql = "UPDATE log_aktivitas SET day_name = {$caseDay}, work_category = {$caseWork} WHERE id IN ({$idList})";

        DB::statement($sql);
    }
}
