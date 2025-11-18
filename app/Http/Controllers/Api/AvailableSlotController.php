<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvailableSlot;
use App\Models\TrainerProfile;
use App\Models\NutritionistProfile;
use Illuminate\Http\Request;

class AvailableSlotController extends Controller
{
    /**
     * Listar slots disponibles de un profesional
     */
    public function index(Request $request, $professionalId)
    {
        $query = AvailableSlot::where('professional_id', $professionalId)
            ->with('bookings');

        // Filtrar por fecha
        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        // Filtrar por rango de fechas
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Solo disponibles
        if ($request->boolean('available_only')) {
            $query->where('status', 'available')
                ->where(function ($q) {
                    $q->where('type', 'individual')
                      ->where('current_participants', 0)
                      ->orWhere(function ($q2) {
                          $q2->where('type', 'grupal')
                             ->whereRaw('current_participants < max_participants');
                      });
                });
        }

        $slots = $query->orderBy('date')->orderBy('start_time')->get();

        return response()->json($slots);
    }

    /**
     * Crear slot disponible (profesional)
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Verificar que sea profesional
        if (!$user->hasAnyRole(['entrenador', 'nutricionista'])) {
            return response()->json([
                'message' => 'Solo profesionales pueden crear horarios',
            ], 403);
        }

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'required|in:individual,grupal',
            'modality' => 'required|in:presencial,virtual',
            'max_participants' => 'nullable|integer|min:1|max:50',
            'notes' => 'nullable|string',
        ]);

        // Validar modalidad virtual solo para nutricionistas
        if ($request->modality === 'virtual' && !$user->hasRole('nutricionista')) {
            return response()->json([
                'message' => 'Solo nutricionistas pueden ofrecer sesiones virtuales',
            ], 400);
        }

        // Verificar conflicto de horarios
        if (AvailableSlot::hasConflict($user->id, $request->date, $request->start_time, $request->end_time)) {
            return response()->json([
                'message' => 'Ya tienes una sesión agendada en ese horario',
            ], 400);
        }

        // Obtener tarifa del profesional
        $profile = $user->hasRole('entrenador') 
            ? $user->trainerProfile 
            : $user->nutritionistProfile;

        if (!$profile || !$profile->hourly_rate) {
            return response()->json([
                'message' => 'Debes configurar tu tarifa por hora primero',
            ], 400);
        }

        // Calcular precio basado en duración
        $startTime = \Carbon\Carbon::parse($request->start_time);
        $endTime = \Carbon\Carbon::parse($request->end_time);
        $durationHours = $endTime->diffInMinutes($startTime) / 60;
        $price = $profile->hourly_rate * $durationHours;

        $slot = AvailableSlot::create([
            'professional_id' => $user->id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'type' => $request->type,
            'modality' => $request->modality,
            'max_participants' => $request->type === 'grupal' ? ($request->max_participants ?? 10) : 1,
            'current_participants' => 0,
            'price' => $price,
            'status' => 'available',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Horario creado exitosamente',
            'slot' => $slot,
        ], 201);
    }

    /**
     * Actualizar slot
     */
    public function update(Request $request, $id)
    {
        $slot = AvailableSlot::where('professional_id', auth()->id())->findOrFail($id);

        // No permitir editar si ya tiene reservas
        if ($slot->current_participants > 0) {
            return response()->json([
                'message' => 'No puedes editar un horario que ya tiene reservas',
            ], 400);
        }

        $request->validate([
            'date' => 'sometimes|date|after_or_equal:today',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
        ]);

        // Verificar conflicto si cambia fecha/hora
        if ($request->has(['date', 'start_time', 'end_time'])) {
            $date = $request->date ?? $slot->date;
            $startTime = $request->start_time ?? $slot->start_time;
            $endTime = $request->end_time ?? $slot->end_time;

            if (AvailableSlot::hasConflict(auth()->id(), $date, $startTime, $endTime, $id)) {
                return response()->json([
                    'message' => 'Ya tienes una sesión agendada en ese horario',
                ], 400);
            }
        }

        $slot->update($request->all());

        return response()->json([
            'message' => 'Horario actualizado exitosamente',
            'slot' => $slot,
        ]);
    }

    /**
     * Cancelar slot
     */
    public function destroy($id)
    {
        $slot = AvailableSlot::where('professional_id', auth()->id())->findOrFail($id);

        // Si tiene reservas, notificar y reembolsar
        if ($slot->current_participants > 0) {
            $slot->update(['status' => 'cancelled']);
            
            // TODO: Reembolsar a todos los clientes con reservas
            
            return response()->json([
                'message' => 'Horario cancelado. Se reembolsará a los clientes.',
            ]);
        }

        $slot->delete();

        return response()->json([
            'message' => 'Horario eliminado exitosamente',
        ]);
    }

    /**
     * Mis horarios (profesional)
     */
    public function mySlots(Request $request)
    {
        $slots = AvailableSlot::where('professional_id', auth()->id())
            ->with('bookings.client')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json($slots);
    }
}
