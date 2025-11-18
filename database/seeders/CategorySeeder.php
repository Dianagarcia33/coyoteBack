<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Proteínas',
                'description' => 'Suplementos proteicos para aumentar masa muscular y recuperación',
                'is_active' => true,
            ],
            [
                'name' => 'Suplementos',
                'description' => 'Vitaminas, minerales y suplementos nutricionales',
                'is_active' => true,
            ],
            [
                'name' => 'Pre-Entreno',
                'description' => 'Suplementos para mejorar el rendimiento y energía antes del entrenamiento',
                'is_active' => true,
            ],
            [
                'name' => 'Accesorios',
                'description' => 'Equipamiento y accesorios para entrenamiento',
                'is_active' => true,
            ],
            [
                'name' => 'Ropa Deportiva',
                'description' => 'Indumentaria para entrenamientos y actividad física',
                'is_active' => true,
            ],
            [
                'name' => 'Snacks Saludables',
                'description' => 'Snacks y barras proteicas para nutrición deportiva',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
