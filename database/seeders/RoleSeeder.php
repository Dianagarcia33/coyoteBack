<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Acceso completo al sistema',
            ],
            [
                'name' => 'gimnasio',
                'display_name' => 'Gimnasio',
                'description' => 'Gestión de instalaciones, equipamiento y membresías del gimnasio',
            ],
            [
                'name' => 'entrenador',
                'display_name' => 'Entrenador',
                'description' => 'Puede gestionar entrenamientos y clientes asignados',
            ],
            [
                'name' => 'nutricionista',
                'display_name' => 'Nutricionista',
                'description' => 'Puede gestionar planes nutricionales y clientes asignados',
            ],
            [
                'name' => 'cliente',
                'display_name' => 'Cliente',
                'description' => 'Acceso a sus propios datos y entrenamientos',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
