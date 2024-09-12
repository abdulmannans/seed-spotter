<?php

namespace Abdulmannans\SeedSpotter;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class SeedSpotter
{
    protected $seeder;
    protected $table;

    public function __construct(Seeder $seeder, string $table)
    {
        $this->seeder = $seeder;
        $this->table = $table;
    }

    public function compare()
    {
        $seederData = $this->getSeederData();
        $databaseData = $this->getDatabaseData();

        $discrepancies = $this->findDiscrepancies($seederData, $databaseData);

        return [
            'has_discrepancies' => !empty($discrepancies),
            'discrepancies' => $discrepancies
        ];
    }

    protected function getSeederData()
    {
        $originalData = DB::table($this->table)->get()->toArray();

        DB::beginTransaction();

        try {
            DB::table($this->table)->delete();
            $this->seeder->run();
            $seederData = DB::table($this->table)->get()->toArray();
            DB::rollBack();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $seederData;
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

            if (!$matchingDbRow) {
                $discrepancies[] = ['type' => 'missing', 'data' => $seederRow];
            } elseif ($this->rowsDiffer($seederRow, $matchingDbRow)) {
                $discrepancies[] = [
                    'type' => 'different',
                    'seeder_data' => $seederRow,
                    'db_data' => $matchingDbRow
                ];
            }
        }

        foreach ($databaseData as $dbRow) {
            if (!$this->findMatchingRow($dbRow, $seederData)) {
                $discrepancies[] = ['type' => 'extra', 'data' => $dbRow];
            }
        }

        return $discrepancies;
    }

    protected function findMatchingRow($row, $dataSet)
    {
        foreach ($dataSet as $dataRow) {
            if ($dataRow->id === $row->id) {
                return $dataRow;
            }
        }
        return null;
    }

    protected function rowsDiffer($row1, $row2)
    {
        foreach ($row1 as $key => $value) {
            if ($row2->$key !== $value) {
                return true;
            }
        }
        return false;
    }
}
