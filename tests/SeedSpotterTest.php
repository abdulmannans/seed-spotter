<?php

use Abdulmannans\SeedSpotter\SeedSpotter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class SeedSpotterTest extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Abdulmannans\SeedSpotter\SeedSpotterServiceProvider::class];
    }

    #[Test]
    public function it_can_detect_missing_rows()
    {
        // Create a test table
        $this->createTestTable();

        // Insert a row into the database
        DB::table('test_table')->insert(['id' => 1, 'name' => 'Test']);

        // Create a seeder that doesn't seed anything
        $seeder = new class extends Seeder
        {
            public function run() {}
        };

        $spotter = new SeedSpotter($seeder, 'test_table');
        $result = $spotter->compare();

        $this->assertTrue($result['has_discrepancies']);
        $this->assertCount(1, $result['discrepancies']);
        $this->assertEquals('extra', $result['discrepancies'][0]['type']);
    }

    #[Test]
    public function it_can_detect_extra_rows()
    {
        // Create a test table
        $this->createTestTable();

        // Create a seeder that seeds one row
        $seeder = new class extends Seeder
        {
            public function run()
            {
                DB::table('test_table')->insert(['id' => 1, 'name' => 'Test']);
            }
        };

        $spotter = new SeedSpotter($seeder, 'test_table');
        $result = $spotter->compare();

        $this->assertTrue($result['has_discrepancies']);
        $this->assertCount(1, $result['discrepancies']);
        $this->assertEquals('missing', $result['discrepancies'][0]['type']);
    }

    protected function createTestTable()
    {
        DB::statement('CREATE TABLE test_table (id INT PRIMARY KEY, name VARCHAR(255))');
    }
}
