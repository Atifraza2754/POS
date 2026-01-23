<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Database\Seeders\Updates\Version12Seeder;
use Database\Seeders\Updates\Version131Seeder;
use Database\Seeders\Updates\Version132Seeder;
use Database\Seeders\Updates\Version133Seeder;
use Database\Seeders\Updates\Version134Seeder;

class VersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

       $newVersionArray = [
            '1.0',  //1.0, Date: 24-10-2024
            '1.1',  //1.0, Date: 28-11-2024
            '1.1.1',  //1.0, Date: 30-11-2024
            '1.2',  //1.2, Date: 07-12-2024
            '1.3',  //1.3, Date: 17-12-2024
            '1.3.1',  //1.3.1, Date: 22-12-2024
            '1.3.2',  //1.3.2, Date: 24-12-2024
            '1.3.3',  //1.3.3, Date: 28-12-2024
            '1.3.4',  //1.3.4, Date: 31-12-2024
            env('APP_VERSION'),  //1.4, Date: 01-01-2025
        ];

        $existingVersions = DB::table('versions')->pluck('version')->toArray();

        foreach ($newVersionArray as $version) {
            //validate is the version exist in it?
            if(!in_array($version, $existingVersions)){
                DB::table('versions')->insert([
                    'version' => $version,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /**
                 * Version wise any seeder updates
                 * */
                $this->updateDatabaseTransaction($version);
            }
        }
    }

    public function updateDatabaseTransaction($version)
    {
        if($version == '1.2'){
            $adminSeeder = new Version12Seeder();
            $adminSeeder->run();
        }
        if($version == '1.3.1'){
            $adminSeeder = new Version131Seeder();
            $adminSeeder->run();
        }
        if($version == '1.3.2'){
            $adminSeeder = new Version132Seeder();
            $adminSeeder->run();
        }
        if($version == '1.3.3'){
            $adminSeeder = new Version133Seeder();
            $adminSeeder->run();
        }
        if($version == '1.3.4'){
            $adminSeeder = new Version134Seeder();
            $adminSeeder->run();
        }
    }

}
