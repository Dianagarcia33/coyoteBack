<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Proteínas
            [
                'category_id' => 1,
                'name' => 'Whey Protein Gold Standard 5 lbs',
                'description' => 'Proteína de suero de leche de alta calidad, ideal para aumentar masa muscular',
                'price' => 89.99,
                'stock' => 50,
                'sku' => 'WP-GS-5LB',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'category_id' => 1,
                'name' => 'Proteína Vegana 2 lbs',
                'description' => 'Proteína vegetal a base de plantas, sin lactosa',
                'price' => 49.99,
                'stock' => 30,
                'sku' => 'PV-2LB',
                'is_active' => true,
            ],
            // Suplementos
            [
                'category_id' => 2,
                'name' => 'Multivitamínico Daily Complete',
                'description' => 'Complejo vitamínico completo para deportistas',
                'price' => 24.99,
                'stock' => 100,
                'sku' => 'MV-DC',
                'is_active' => true,
            ],
            [
                'category_id' => 2,
                'name' => 'Omega 3 Fish Oil 1000mg',
                'description' => 'Aceite de pescado rico en Omega 3 para salud cardiovascular',
                'price' => 19.99,
                'stock' => 80,
                'sku' => 'OM3-1000',
                'is_active' => true,
            ],
            // Pre-Entreno
            [
                'category_id' => 3,
                'name' => 'Pre-Workout Explosive Energy',
                'description' => 'Fórmula pre-entreno con cafeína y beta-alanina',
                'price' => 39.99,
                'discount_price' => 34.99,
                'stock' => 45,
                'sku' => 'PWO-EXP',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'category_id' => 3,
                'name' => 'Creatina Monohidrato 300g',
                'description' => 'Creatina pura para mejorar fuerza y rendimiento',
                'price' => 29.99,
                'stock' => 60,
                'sku' => 'CRE-300',
                'is_active' => true,
            ],
            // Accesorios
            [
                'category_id' => 4,
                'name' => 'Shaker con Compartimentos',
                'description' => 'Botella mezcladora con compartimentos para suplementos',
                'price' => 12.99,
                'stock' => 150,
                'sku' => 'SHK-COMP',
                'is_active' => true,
            ],
            [
                'category_id' => 4,
                'name' => 'Guantes de Entrenamiento Pro',
                'description' => 'Guantes acolchados para levantamiento de pesas',
                'price' => 18.99,
                'stock' => 75,
                'sku' => 'GLV-PRO',
                'is_active' => true,
            ],
            // Ropa Deportiva
            [
                'category_id' => 5,
                'name' => 'Camiseta Dry-Fit Atlética',
                'description' => 'Camiseta deportiva con tecnología de secado rápido',
                'price' => 24.99,
                'stock' => 120,
                'sku' => 'TSH-DF',
                'is_active' => true,
            ],
            [
                'category_id' => 5,
                'name' => 'Shorts de Compresión',
                'description' => 'Shorts ajustados para mejor rendimiento muscular',
                'price' => 34.99,
                'stock' => 90,
                'sku' => 'SHT-COMP',
                'is_active' => true,
            ],
            // Snacks Saludables
            [
                'category_id' => 6,
                'name' => 'Barra Proteica Chocolate Pack x12',
                'description' => 'Barras proteicas sabor chocolate, 20g de proteína cada una',
                'price' => 19.99,
                'stock' => 200,
                'sku' => 'BAR-CHOC-12',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'category_id' => 6,
                'name' => 'Mantequilla de Maní Natural 500g',
                'description' => 'Mantequilla de maní 100% natural sin azúcar añadida',
                'price' => 9.99,
                'stock' => 110,
                'sku' => 'PB-NAT-500',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
