<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\AvailableSlot;
use App\Models\Payment;
use App\Models\User;
use App\Services\EpaycoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    protected $epaycoService;

    public function __construct(EpaycoService $epaycoService)
    {
        $this->epaycoService = $epaycoService;
    }

    /**
     * Reservar un horario (cliente)
     */
    public function store(Request $request)
    {
        $request->validate([
            'professional_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'required|in:individual,grupal',
            'modality' => 'required|in:presencial,virtual',
        ]);

        DB::beginTransaction();
        try {
            // Obtener el profesional y su perfil (con roles precargados)
            $professional = User::with('roles')->findOrFail($request->professional_id);
            
            // Verificar que sea un profesional válido
            if (!$professional->hasAnyRole(['entrenador', 'nutricionista'])) {
                return response()->json([
                    'message' => 'El usuario seleccionado no es un profesional',
                ], 400);
            }

            // Obtener tarifa por hora del profesional
            $profile = null;
            if ($professional->hasRole('entrenador')) {
                $profile = $professional->trainerProfile;
            } else {
                $profile = $professional->nutritionistProfile;
            }

            if (!$profile || !$profile->hourly_rate) {
                return response()->json([
                    'message' => 'El profesional no tiene tarifa configurada',
                ], 400);
            }

            // Verificar modalidad virtual solo para nutricionistas
            if ($request->modality === 'virtual' && !$professional->hasRole('nutricionista')) {
                return response()->json([
                    'message' => 'Solo los nutricionistas pueden ofrecer sesiones virtuales',
                ], 400);
            }

            // Buscar si ya existe un slot disponible en ese horario exacto
            $existingSlot = AvailableSlot::where('professional_id', $request->professional_id)
                ->where('date', $request->date)
                ->where('start_time', $request->start_time)
                ->where('end_time', $request->end_time)
                ->where('type', $request->type)
                ->where('modality', $request->modality)
                ->where('status', '!=', 'full')
                ->first();

            if ($existingSlot) {
                // Unirse a un slot existente
                $slot = $existingSlot;
                $price = $slot->price;
            } else {
                // Verificar que no exista conflicto de horarios (solapamiento)
                $hasConflict = AvailableSlot::hasConflict(
                    $request->professional_id,
                    $request->date,
                    $request->start_time,
                    $request->end_time
                );

                if ($hasConflict) {
                    return response()->json([
                        'message' => 'El profesional ya tiene una sesión en este horario',
                    ], 400);
                }

                // Calcular precio
                $start = \Carbon\Carbon::createFromFormat('H:i', $request->start_time);
                $end = \Carbon\Carbon::createFromFormat('H:i', $request->end_time);
                $durationInMinutes = $start->diffInMinutes($end);
                
                if ($durationInMinutes <= 0) {
                    return response()->json([
                        'message' => 'La hora de fin debe ser posterior a la hora de inicio',
                    ], 400);
                }
                
                $durationInHours = $durationInMinutes / 60;
                $price = $profile->hourly_rate * $durationInHours;

                // Crear nuevo slot
                $slot = AvailableSlot::create([
                    'professional_id' => $request->professional_id,
                    'date' => $request->date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'type' => $request->type,
                    'modality' => $request->modality,
                    'max_participants' => $request->type === 'individual' ? 1 : ($request->max_participants ?? 10),
                    'current_participants' => 0,
                    'price' => $price,
                    'status' => 'available',
                ]);
            }

            // Crear el pago
            $payment = Payment::create([
                'user_id' => auth()->id(),
                'amount' => $price,
                'currency' => 'COP',
                'status' => 'pending',
                'description' => "Sesión {$request->type} con {$professional->name} - {$request->date} {$request->start_time}",
            ]);

            // Crear la reserva
            $booking = Booking::create([
                'slot_id' => $slot->id,
                'client_id' => auth()->id(),
                'payment_id' => $payment->id,
                'amount' => $price,
                'status' => 'pending_confirmation',
            ]);

            DB::commit();

            // Generar URL de pago de ePayco
            $client = auth()->user();
            $checkoutData = $this->epaycoService->buildCheckout(
                $payment->id,
                $price,
                "Sesión {$request->type} - {$professional->name}",
                $client
            );

            \Log::info('Checkout data generado', [
                'payment_id' => $payment->id,
                'checkout_data' => $checkoutData,
            ]);

            $booking->load(['slot', 'payment', 'client']);

            return response()->json([
                'message' => 'Reserva creada. Completa el pago para confirmar.',
                'booking' => $booking,
                'checkout_url' => $checkoutData['checkout_url'],
                'payment_id' => $payment->id,
                'amount' => $price,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la reserva',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmar reserva (profesional)
     */
    public function confirm($id)
    {
        $booking = Booking::whereHas('slot', function ($q) {
            $q->where('professional_id', auth()->id());
        })->findOrFail($id);

        if ($booking->status !== 'pending_confirmation') {
            return response()->json([
                'message' => 'Esta reserva ya fue procesada',
            ], 400);
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reserva confirmada exitosamente',
            'booking' => $booking->load('slot', 'client'),
        ]);
    }

    /**
     * Rechazar reserva (profesional)
     */
    public function reject(Request $request, $id)
    {
        $booking = Booking::whereHas('slot', function ($q) {
            $q->where('professional_id', auth()->id());
        })->findOrFail($id);

        if ($booking->status !== 'pending_confirmation') {
            return response()->json([
                'message' => 'Esta reserva ya fue procesada',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Actualizar reserva
            $booking->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'cancellation_reason' => $request->reason ?? 'Rechazada por el profesional',
            ]);

            // Decrementar participantes en el slot
            $slot = $booking->slot;
            $slot->decrement('current_participants');
            
            if ($slot->status === 'full') {
                $slot->update(['status' => 'available']);
            }

            // Reembolsar: quitar de frozen y devolver al cliente
            $professional = $slot->professional;
            $wallet = $professional->wallet;
            $wallet->subtractFrozenBalance($booking->amount);

            // Actualizar pago como reembolsado
            if ($booking->payment) {
                $booking->payment->update(['status' => 'refunded']);
            }

            // TODO: Devolver dinero real al cliente (integración con pasarela)

            DB::commit();

            return response()->json([
                'message' => 'Reserva rechazada. Se ha reembolsado al cliente.',
                'booking' => $booking,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al rechazar la reserva',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Completar sesión (profesional)
     */
    public function complete(Request $request, $id)
    {
        $booking = Booking::whereHas('slot', function ($q) {
            $q->where('professional_id', auth()->id());
        })->findOrFail($id);

        if (!in_array($booking->status, ['confirmed', 'pending_confirmation'])) {
            return response()->json([
                'message' => 'Solo puedes completar reservas confirmadas',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Actualizar reserva
            $booking->update([
                'status' => 'completed',
                'completed_at' => now(),
                'professional_notes' => $request->notes,
            ]);

            // Mover dinero de frozen a available
            $professional = $booking->slot->professional;
            $wallet = $professional->wallet;
            $wallet->moveFrozenToAvailable($booking->amount, 'session_completed', "Sesión completada {$booking->slot->date}");

            // Marcar slot como completado si es individual o todos completaron
            $slot = $booking->slot;
            if ($slot->type === 'individual' || 
                $slot->bookings()->where('status', 'completed')->count() === $slot->current_participants) {
                $slot->update(['status' => 'completed']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Sesión completada. El pago está disponible en tu wallet.',
                'booking' => $booking->load('slot', 'client'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al completar la sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar reserva (cliente)
     */
    public function cancel(Request $request, $id)
    {
        $booking = Booking::where('client_id', auth()->id())->findOrFail($id);

        if (!in_array($booking->status, ['pending_confirmation', 'confirmed'])) {
            return response()->json([
                'message' => 'Esta reserva no puede ser cancelada',
            ], 400);
        }

        // Verificar política de cancelación (24h)
        $canCancel = $booking->canBeCancelled();
        $refundAmount = $canCancel ? $booking->amount : $booking->amount * 0.5; // 50% penalización

        DB::beginTransaction();
        try {
            $booking->update([
                'status' => 'cancelled_by_client',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->reason ?? 'Cancelada por el cliente',
            ]);

            // Decrementar participantes
            $slot = $booking->slot;
            $slot->decrement('current_participants');
            
            if ($slot->status === 'full') {
                $slot->update(['status' => 'available']);
            }

            // Reembolso parcial o total
            $professional = $slot->professional;
            $wallet = $professional->wallet;
            
            if ($canCancel) {
                // Reembolso completo
                $wallet->subtractFrozenBalance($booking->amount);
            } else {
                // Penalización: 50% para profesional, 50% reembolso
                $wallet->moveFrozenToAvailable($booking->amount * 0.5, 'cancellation_penalty', 'Penalización por cancelación tardía');
                $wallet->subtractFrozenBalance($booking->amount * 0.5);
            }

            DB::commit();

            $message = $canCancel 
                ? 'Reserva cancelada. Recibirás reembolso completo.'
                : 'Reserva cancelada con menos de 24h. Reembolso del 50%.';

            return response()->json([
                'message' => $message,
                'refund_amount' => $refundAmount,
                'booking' => $booking,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cancelar la reserva',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mis reservas (cliente)
     */
    public function myBookings()
    {
        $bookings = Booking::where('client_id', auth()->id())
            ->with(['slot.professional', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bookings);
    }

    /**
     * Reservas pendientes de confirmación (profesional)
     */
    public function pendingConfirmations()
    {
        $bookings = Booking::whereHas('slot', function ($q) {
            $q->where('professional_id', auth()->id());
        })
        ->where('status', 'pending_confirmation')
        ->with(['slot', 'client'])
        ->orderBy('created_at')
        ->get();

        return response()->json($bookings);
    }

    /**
     * Todas las reservas del profesional
     */
    public function professionalBookings()
    {
        $bookings = Booking::whereHas('slot', function ($q) {
            $q->where('professional_id', auth()->id());
        })
        ->with(['slot', 'client', 'payment'])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($bookings);
    }
}
