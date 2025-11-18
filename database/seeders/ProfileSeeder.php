<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\GymProfile;
use App\Models\TrainerProfile;
use App\Models\NutritionistProfile;
use App\Models\ClientProfile;
use App\Models\ClientGoal;
use App\Models\ClientMeasurement;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Perfil de Gimnasio
        $gimnasio = User::where('email', 'gimnasio@coyote.com')->first();
        if ($gimnasio) {
            GymProfile::create([
                'user_id' => $gimnasio->id,
                'business_name' => 'Coyote Fitness Center',
                'gym_type' => 'Funcional',
                'specialties' => ['Musculación', 'CrossFit', 'Cardio', 'Yoga'],
                'address' => 'Calle 100 #15-20, Bogotá',
                'phone' => '+57 301 234 5678',
                'opening_hours' => 'Lun-Vie: 5am-10pm, Sab-Dom: 7am-8pm',
                'machines' => [
                    ['name' => 'Caminadoras', 'quantity' => 10],
                    ['name' => 'Bicicletas', 'quantity' => 8],
                    ['name' => 'Máquinas de peso', 'quantity' => 15],
                    ['name' => 'Pesas libres', 'quantity' => 20],
                ],
                'classes' => [
                    ['name' => 'Spinning', 'schedule' => 'Lun-Mie-Vie 6am, 7pm'],
                    ['name' => 'Yoga', 'schedule' => 'Mar-Jue 7am, 6pm'],
                    ['name' => 'CrossFit', 'schedule' => 'Lun-Sab 8am, 5pm'],
                    ['name' => 'Zumba', 'schedule' => 'Mar-Jue-Sab 7pm'],
                ],
                'description' => 'El mejor gimnasio de Bogotá con instalaciones de primera y entrenadores certificados.',
                'photos' => [
                    'https://example.com/gym1.jpg',
                    'https://example.com/gym2.jpg',
                    'https://example.com/gym3.jpg',
                ],
                'social_media' => [
                    'instagram' => '@coyotefitness',
                    'facebook' => 'CoyoteFitnessCenter',
                    'tiktok' => '@coyotefitness',
                ],
            ]);
        }

        // Perfil de Entrenador
        $entrenador = User::where('email', 'entrenador@coyote.com')->first();
        if ($entrenador) {
            TrainerProfile::create([
                'user_id' => $entrenador->id,
                'specialization' => 'Entrenamiento Funcional y Fuerza',
                'hourly_rate' => 50000,
                'bio' => 'Entrenador certificado con 8 años de experiencia en transformación física y entrenamiento de alto rendimiento.',
                'certifications' => [
                    'NSCA-CPT (Certified Personal Trainer)',
                    'CrossFit Level 2',
                    'Nutrición Deportiva Básica',
                ],
                'years_experience' => 8,
                'availability' => [
                    'monday' => ['6:00-10:00', '16:00-20:00'],
                    'tuesday' => ['6:00-10:00', '16:00-20:00'],
                    'wednesday' => ['6:00-10:00', '16:00-20:00'],
                    'thursday' => ['6:00-10:00', '16:00-20:00'],
                    'friday' => ['6:00-10:00', '16:00-20:00'],
                    'saturday' => ['8:00-12:00'],
                ],
                'photo' => 'https://example.com/trainer.jpg',
            ]);
        }

        // Perfil de Nutricionista
        $nutricionista = User::where('email', 'nutricionista@coyote.com')->first();
        if ($nutricionista) {
            NutritionistProfile::create([
                'user_id' => $nutricionista->id,
                'specialization' => 'Nutrición Deportiva y Clínica',
                'hourly_rate' => 60000,
                'bio' => 'Nutricionista especializada en deportistas de alto rendimiento y planes personalizados de pérdida de peso.',
                'certifications' => [
                    'Licenciatura en Nutrición y Dietética',
                    'Maestría en Nutrición Deportiva',
                    'Certificación ISSN',
                ],
                'years_experience' => 6,
                'availability' => [
                    'monday' => ['8:00-12:00', '14:00-18:00'],
                    'tuesday' => ['8:00-12:00', '14:00-18:00'],
                    'wednesday' => ['8:00-12:00', '14:00-18:00'],
                    'thursday' => ['8:00-12:00', '14:00-18:00'],
                    'friday' => ['8:00-12:00', '14:00-18:00'],
                ],
                'photo' => 'https://example.com/nutritionist.jpg',
            ]);
        }

        // Perfil de Cliente
        $cliente = User::where('email', 'cliente@coyote.com')->first();
        if ($cliente) {
            ClientProfile::create([
                'user_id' => $cliente->id,
                'birth_date' => '1990-05-15',
                'gender' => 'male',
                'height' => 175,
                'weight' => 80,
                'activity_level' => 'moderate',
                'dietary_preferences' => ['No gluten'],
                'allergies' => 'Alergia a mariscos',
                'medical_conditions' => 'Ninguna',
                'emergency_contact_name' => 'María Cliente',
                'emergency_contact_phone' => '+57 301 987 6543',
                'emergency_contact_relationship' => 'Esposa',
                'photo' => 'https://example.com/client.jpg',
            ]);

            // Metas del cliente
            ClientGoal::create([
                'client_id' => $cliente->id,
                'title' => 'Perder 10 kg',
                'description' => 'Bajar de 80kg a 70kg en 3 meses',
                'target_weight' => 70,
                'start_date' => now()->subDays(30),
                'target_date' => now()->addDays(60),
                'status' => 'active',
                'progress' => 30,
            ]);

            ClientGoal::create([
                'client_id' => $cliente->id,
                'title' => 'Aumentar masa muscular',
                'description' => 'Ganar 3kg de músculo',
                'target_metric' => 'muscle_mass',
                'target_value' => 63,
                'start_date' => now()->subDays(15),
                'target_date' => now()->addDays(75),
                'status' => 'active',
                'progress' => 15,
            ]);

            ClientGoal::create([
                'client_id' => $cliente->id,
                'title' => 'Correr 5km sin parar',
                'description' => 'Completar 5km en menos de 30 minutos',
                'start_date' => now()->subDays(60),
                'target_date' => now()->subDays(5),
                'status' => 'completed',
                'progress' => 100,
            ]);

            // Medidas del cliente
            ClientMeasurement::create([
                'client_id' => $cliente->id,
                'weight' => 82,
                'body_fat' => 22,
                'muscle_mass' => 60,
                'notes' => 'Medición inicial',
                'measured_at' => now()->subDays(30),
            ]);

            ClientMeasurement::create([
                'client_id' => $cliente->id,
                'weight' => 79,
                'body_fat' => 20,
                'muscle_mass' => 61,
                'notes' => 'Progreso después de 15 días',
                'measured_at' => now()->subDays(15),
            ]);

            ClientMeasurement::create([
                'client_id' => $cliente->id,
                'weight' => 77,
                'body_fat' => 18,
                'muscle_mass' => 62,
                'notes' => 'Medición actual - excelente progreso',
                'measured_at' => now(),
            ]);

            // Reviews del cliente a profesionales
            if ($entrenador) {
                Review::create([
                    'user_id' => $entrenador->id,
                    'client_id' => $cliente->id,
                    'rating' => 5,
                    'comment' => 'Excelente entrenador, muy profesional y motivador. Recomendado 100%',
                ]);
            }

            if ($nutricionista) {
                Review::create([
                    'user_id' => $nutricionista->id,
                    'client_id' => $cliente->id,
                    'rating' => 5,
                    'comment' => 'La mejor nutricionista, planes muy personalizados y resultados comprobados.',
                ]);
            }
        }
    }
}
