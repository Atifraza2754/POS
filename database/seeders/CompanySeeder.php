<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'id'                    => 1,
            'name'                  => 'MYPOS',
            'mobile'                => '9999999999',
            'email'                =>  'company@example.com',
            'address'               => 'Address Line 1, City, State, Zip',
            'language_code'         => null,
            'language_name'         => null,
            'timezone'              => 'Asia/Karachi',
            'date_format'           => 'Y-m-d',
            'time_format'           => '24',
        ]);
    }
}
