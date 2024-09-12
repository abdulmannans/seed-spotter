<?php

namespace Abdulmannans\SeedSpotter\Console;

use Illuminate\Console\Command;
use Abdulmannans\SeedSpotter\SeedSpotter;

class CompareSeedsCommand extends Command
{
    protected $signature = 'seed-spotter:compare {seeder} {--table= : The table to compare}';

    protected $description = 'Compare seeder data with database data';

    public function handle()
    {
        $seederClass = $this->argument('seeder');
        $table = $this->option('table') ?? config('seed-spotter.table');

        $seeder = new $seederClass;
        $spotter = new SeedSpotter($seeder, $table);

        $result = $spotter->compare();

        if ($result['has_discrepancies']) {
            $this->error("Discrepancies found!");
            $this->table(['Type', 'Details'], $this->formatDiscrepancies($result['discrepancies']));
        } else {
            $this->info("No discrepancies found. Seeder and database are in sync.");
        }
    }

    protected function formatDiscrepancies($discrepancies)
    {
        return collect($discrepancies)->map(function ($discrepancy) {
            return [
                $discrepancy['type'],
                json_encode($discrepancy['data'] ?? $discrepancy, JSON_PRETTY_PRINT)
            ];
        })->toArray();
    }
}
