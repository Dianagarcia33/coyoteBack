<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario Admin
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@coyote.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        // Usuario Gimnasio
        $gimnasio = User::create([
            'name' => 'Gimnasio Manager',
            'email' => 'gimnasio@coyote.com',
            'password' => Hash::make('password'),
        ]);
        $gimnasio->assignRole('gimnasio');

        // Usuario Entrenador
        $entrenador = User::create([
            'name' => 'Carlos Entrenador',
            'email' => 'entrenador@coyote.com',
            'password' => Hash::make('password'),
        ]);
        $entrenador->assignRole('entrenador');

        // Usuario Nutricionista
        $nutricionista = User::create([
            'name' => 'Ana Nutricionista',
            'email' => 'nutricionista@coyote.com',
            'password' => Hash::make('password'),
        ]);
        $nutricionista->assignRole('nutricionista');

        // Usuario Cliente
        $cliente = User::create([
            'name' => 'Juan Cliente',
            'email' => 'cliente@coyote.com',
            'password' => Hash::make('password'),
        ]);
        $cliente->assignRole('cliente');
    }
}
