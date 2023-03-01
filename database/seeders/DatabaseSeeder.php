<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Hash;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
       User::create([
        'email'=>'pogipol@gmail.com',
        'password'=>Hash::make('password'),
        'account_is_verified'=>1,
       ]);
       User::create([
        'email'=>'admin@gmail.com',
        'password'=>Hash::make('password'),
        'account_is_verified'=>1,
       ]);
        // \App\Models\User::factory(10)->create();
    }
}

