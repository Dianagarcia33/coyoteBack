<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AvailableSlot;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios
        $entrenador = User::whereHas('roles', function ($q) {
            $q->where('name', 'entrenador');
        })->first();

        $nutricionista = User::whereHas('roles', function ($q) {
            $q->where('name', 'nutricionista');
        })->first();

        $cliente1 = User::whereHas('roles', function ($q) {
            $q->where('name', 'cliente');
        })->first();

        $cliente2 = User::whereHas('roles', function ($q) {
            $q->where('name', 'cliente');
        })->skip(1)->first();

        if (!$entrenador || !$cliente1) {
            $this->command->error('No se encontraron usuarios necesarios. Ejecuta ProfileSeeder primero.');
            return;
        }

        // Obtener tarifas
        $entrenadorRate = $entrenador->trainerProfile->hourly_rate ?? 50000;
        $nutricionistaRate = $nutricionista?->nutritionistProfile->hourly_rate ?? 60000;

        $this->command->info('Creando slots y reservas...');

        // 1. Sesión individual confirmada (entrenador) - Hoy 8am
        $slot1 = AvailableSlot::create([
            'professional_id' => $entrenador->id,
            'date' => Carbon::today(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'type' => 'individual',
            'modality' => 'presencial',
            'max_participants' => 1,
            'current_participants' => 1,
            'price' => $entrenadorRate,
            'status' => 'full',
        ]);

        $payment1 = Payment::create([
            'user_id' => $cliente1->id,
            'amount' => $entrenadorRate,
            'currency' => 'COP',
            'status' => 'approved',
            'description' => "Sesión individual - {$slot1->date} {$slot1->start_time}",
        ]);

        $booking1 = Booking::create([
            'slot_id' => $slot1->id,
            'client_id' => $cliente1->id,
            'payment_id' => $payment1->id,
            'amount' => $entrenadorRate,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Congelar dinero en wallet del profesional
        $entrenador->getOrCreateWallet()->addFrozenBalance($entrenadorRate, 'booking', "Reserva sesión {$slot1->date}");

        // 2. Sesión pendiente de confirmación - Mañana 10am
        $slot2 = AvailableSlot::create([
            'professional_id' => $entrenador->id,
            'date' => Carbon::tomorrow(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'type' => 'individual',
            'modality' => 'presencial',
            'max_participants' => 1,
            'current_participants' => 1,
            'price' => $entrenadorRate,
            'status' => 'full',
        ]);

        $payment2 = Payment::create([
            'user_id' => $cliente1->id,
            'amount' => $entrenadorRate,
            'currency' => 'COP',
            'status' => 'approved',
            'description' => "Sesión individual - {$slot2->date} {$slot2->start_time}",
        ]);

        Booking::create([
            'slot_id' => $slot2->id,
            'client_id' => $cliente1->id,
            'payment_id' => $payment2->id,
            'amount' => $entrenadorRate,
            'status' => 'pending_confirmation',
        ]);

        $entrenador->wallet->addFrozenBalance($entrenadorRate, 'booking', "Reserva sesión {$slot2->date}");

        // 3. Sesión grupal (Parche Fit) con 3 clientes - Pasado mañana 6pm
        $slot3 = AvailableSlot::create([
            'professional_id' => $entrenador->id,
            'date' => Carbon::today()->addDays(2),
            'start_time' => '18:00',
            'end_time' => '19:00',
            'type' => 'grupal',
            'modality' => 'presencial',
            'max_participants' => 10,
            'current_participants' => 3,
            'price' => $entrenadorRate,
            'status' => 'available',
        ]);

        // Cliente 1 en sesión grupal
        $payment3 = Payment::create([
            'user_id' => $cliente1->id,
            'amount' => $entrenadorRate,
            'currency' => 'COP',
            'status' => 'approved',
            'description' => "Sesión grupal - {$slot3->date} {$slot3->start_time}",
        ]);

        Booking::create([
            'slot_id' => $slot3->id,
            'client_id' => $cliente1->id,
            'payment_id' => $payment3->id,
            'amount' => $entrenadorRate,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $entrenador->wallet->addFrozenBalance($entrenadorRate, 'booking', "Reserva sesión {$slot3->date}");

        // Cliente 2 en sesión grupal
        if ($cliente2) {
            $payment4 = Payment::create([
                'user_id' => $cliente2->id,
                'amount' => $entrenadorRate,
                'currency' => 'COP',
                'status' => 'approved',
                'description' => "Sesión grupal - {$slot3->date} {$slot3->start_time}",
            ]);

            Booking::create([
                'slot_id' => $slot3->id,
                'client_id' => $cliente2->id,
                'payment_id' => $payment4->id,
                'amount' => $entrenadorRate,
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            $entrenador->wallet->addFrozenBalance($entrenadorRate, 'booking', "Reserva sesión {$slot3->date}");
        }

        // Admin como tercer cliente
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->first();

        if ($admin) {
            $payment5 = Payment::create([
                'user_id' => $admin->id,
                'amount' => $entrenadorRate,
                'currency' => 'COP',
                'status' => 'approved',
                'description' => "Sesión grupal - {$slot3->date} {$slot3->start_time}",
            ]);

            Booking::create([
                'slot_id' => $slot3->id,
                'client_id' => $admin->id,
                'payment_id' => $payment5->id,
                'amount' => $entrenadorRate,
                'status' => 'pending_confirmation',
            ]);

            $entrenador->wallet->addFrozenBalance($entrenadorRate, 'booking', "Reserva sesión {$slot3->date}");
        }

        // 4. Sesión completada - Hace 2 días
        $slot4 = AvailableSlot::create([
            'professional_id' => $entrenador->id,
            'date' => Carbon::today()->subDays(2),
            'start_time' => '07:00',
            'end_time' => '08:00',
            'type' => 'individual',
            'modality' => 'presencial',
            'max_participants' => 1,
            'current_participants' => 1,
            'price' => $entrenadorRate,
            'status' => 'completed',
        ]);

        $payment6 = Payment::create([
            'user_id' => $cliente1->id,
            'amount' => $entrenadorRate,
            'currency' => 'COP',
            'status' => 'approved',
            'description' => "Sesión individual - {$slot4->date} {$slot4->start_time}",
        ]);

        Booking::create([
            'slot_id' => $slot4->id,
            'client_id' => $cliente1->id,
            'payment_id' => $payment6->id,
            'amount' => $entrenadorRate,
            'status' => 'completed',
            'confirmed_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addHour(),
            'professional_notes' => 'Excelente sesión, buen progreso',
        ]);

        // Esta ya movió dinero de frozen a available
        $entrenador->getOrCreateWallet()->moveFrozenToAvailable($entrenadorRate, 'session_completed', "Sesión completada {$slot4->date}");

        // 5. Sesión virtual con nutricionista (si existe)
        if ($nutricionista) {
            $slot5 = AvailableSlot::create([
                'professional_id' => $nutricionista->id,
                'date' => Carbon::today()->addDays(3),
                'start_time' => '15:00',
                'end_time' => '16:00',
                'type' => 'individual',
                'modality' => 'virtual',
                'max_participants' => 1,
                'current_participants' => 1,
                'price' => $nutricionistaRate,
                'status' => 'full',
            ]);

            $payment7 = Payment::create([
                'user_id' => $cliente1->id,
                'amount' => $nutricionistaRate,
                'currency' => 'COP',
                'status' => 'approved',
                'description' => "Sesión virtual nutrición - {$slot5->date} {$slot5->start_time}",
            ]);

            Booking::create([
                'slot_id' => $slot5->id,
                'client_id' => $cliente1->id,
                'payment_id' => $payment7->id,
                'amount' => $nutricionistaRate,
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            $nutricionista->getOrCreateWallet()->addFrozenBalance($nutricionistaRate, 'booking', "Reserva sesión {$slot5->date}");
        }

        $this->command->info('✅ Slots y reservas creadas exitosamente:');
        $this->command->info('   - 1 sesión confirmada (hoy)');
        $this->command->info('   - 1 sesión pendiente confirmación (mañana)');
        $this->command->info('   - 1 sesión grupal con 3 clientes (pasado mañana)');
        $this->command->info('   - 1 sesión completada (hace 2 días)');
        if ($nutricionista) {
            $this->command->info('   - 1 sesión virtual con nutricionista (3 días)');
        }
    }
}
