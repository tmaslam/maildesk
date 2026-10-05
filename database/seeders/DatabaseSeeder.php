<?php

namespace Database\Seeders;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Admin', 'email' => 'admin@portal.local', 'password' => 'admin123', 'role' => 'admin']
        );

        foreach ([
            ['brand_name' => '1Dollar Digitizing', 'website' => '1dollardigitizing.com', 'sort_order' => 1],
            ['brand_name' => 'Aplus Digitizing',   'website' => 'aplusdigitizing.com',     'sort_order' => 2],
            ['brand_name' => 'Digitizing Zone',    'website' => 'digitizingzone.com',      'sort_order' => 3],
        ] as $brand) {
            Mailbox::firstOrCreate(['brand_name' => $brand['brand_name']], $brand);
        }
    }
}

