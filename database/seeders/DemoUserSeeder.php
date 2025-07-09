<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
          User::create([
            'name' => 'Demo User',
            'email' => 'demo@coyote.com',
            'password' => bcrypt('password123'),
            'role' => 'cliente',
            'profile_pic' => null,
            'document_verified' => true,
        ]);
    }
}
