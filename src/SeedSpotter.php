<?php

namespace Abdulmannans\SeedSpotter;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedSpotter
{
    protected $seeder;

    protected $table;

    protected $ignoreColumns;

    public function __construct(Seeder $seeder, string $table, array $ignoreColumns = [])
    {
        $this->seeder = $seeder;
        $this->table = $table;
        $this->ignoreColumns = $ignoreColumns;
    }

    public function compare()
    {
        $seederData = $this->getSeederData();
        $databaseData = $this->getDatabaseData();

        $discrepancies = $this->findDiscrepancies($seederData, $databaseData);

        return [
            'has_discrepancies' => ! empty($discrepancies),
            'discrepancies' => $discrepancies,
        ];
    }

    protected function getSeederData()
    {
        // Create a temporary table to store the original data
        $tempTable = $this->table.'_temp_'.time();
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("CREATE TABLE {$tempTable} AS SELECT * FROM {$this->table} WHERE 1=0");
            DB::statement("INSERT INTO {$tempTable} SELECT * FROM {$this->table}");
        } else {
            DB::statement("CREATE TABLE {$tempTable} LIKE {$this->table}");
            DB::statement("INSERT INTO {$tempTable} SELECT * FROM {$this->table}");
        }

        try {
            // Clear the table before running the seeder
            if ($driver === 'sqlite') {
                DB::statement("DELETE FROM {$this->table}");
            } else {
                DB::statement("TRUNCATE TABLE {$this->table}");
            }

            // Run the seeder
            $this->seeder->run();

            // Get the seeded data
            $seededData = DB::table($this->table)->get();

            // Restore the original data
            if ($driver === 'sqlite') {
                DB::statement("DELETE FROM {$this->table}");
            } else {
                DB::statement("TRUNCATE TABLE {$this->table}");
            }
            DB::statement("INSERT INTO {$this->table} SELECT * FROM {$tempTable}");

            // Drop the temporary table
            DB::statement("DROP TABLE {$tempTable}");

            return $seededData->toArray();
        } catch (\Exception $e) {
            // If an error occurs, make sure we restore the original data and drop the temp table
            if (DB::getSchemaBuilder()->hasTable($tempTable)) {
                if ($driver === 'sqlite') {
                    DB::statement("DELETE FROM {$this->table}");
                } else {
                    DB::statement("TRUNCATE TABLE {$this->table}");
                }
                DB::statement("INSERT INTO {$this->table} SELECT * FROM {$tempTable}");
                DB::statement("DROP TABLE {$tempTable}");
            }
            throw $e;
        }
    }

    protected function getDatabaseData()
    {
        return DB::table($this->table)->get()->toArray();
    }

    protected function findDiscrepancies($seederData, $databaseData)
    {
        $discrepancies = [];

        foreach ($seederData as $seederRow) {
            $matchingDbRow = $this->findMatchingRow($seederRow, $databaseData);

            if (! $matchingDbRow) {
                $discrepancies[] = ['type' => 'missing', 'data' => $seederRow];
            } elseif ($this->rowsDiffer($seederRow, $matchingDbRow)) {
                $discrepancies[] = [
                    'type' => 'different',
                    'seeder_data' => $seederRow,
                    'db_data' => $matchingDbRow,
                ];
            }
        }

        foreach ($databaseData as $dbRow) {
            if (! $this->findMatchingRow($dbRow, $seederData)) {
                $discrepancies[] = ['type' => 'extra', 'data' => $dbRow];
            }
        }

        return $discrepancies;
    }

    protected function findMatchingRow($row, $dataSet)
    {
        foreach ($dataSet as $dataRow) {
            $match = true;
            foreach ($row as $key => $value) {
                if (in_array($key, $this->ignoreColumns)) {
                    continue;
                }
                if (! property_exists($dataRow, $key) || $value !== $dataRow->$key) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $dataRow;
            }
        }

        return null;
    }

    protected function rowsDiffer($row1, $row2)
    {
        foreach ($row1 as $key => $value) {
            if (in_array($key, $this->ignoreColumns)) {
                continue;
            }
            if (! property_exists($row2, $key) || $value !== $row2->$key) {
                return true;
            }
        }

        return false;
    }
}
