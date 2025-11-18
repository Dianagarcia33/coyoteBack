<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvailableSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailableHoursController extends Controller
{
    /**
     * Obtener todas las horas disponibles de un profesional en un día
     */
    public function getAvailableHours(Request $request, $professionalId)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        // Verificar que el profesional existe
        $professional = User::findOrFail($professionalId);
        
        if (!in_array($professional->role, ['entrenador', 'nutricionista'])) {
            return response()->json([
                'message' => 'El usuario no es un profesional',
            ], 400);
        }

        // Obtener perfil del profesional
        $profile = null;
        if ($professional->role === 'entrenador') {
            $profile = $professional->trainerProfile;
        } else {
            $profile = $professional->nutritionistProfile;
        }

        if (!$profile) {
            return response()->json([
                'available_hours' => [],
                'message' => 'El profesional no tiene perfil configurado',
            ]);
        }

        // Obtener horarios ocupados del día
        $occupiedSlots = AvailableSlot::where('professional_id', $professionalId)
            ->where('date', $request->date)
            ->whereIn('status', ['available', 'full', 'confirmed'])
            ->get(['start_time', 'end_time', 'status', 'type', 'current_participants', 'max_participants']);

        // Generar todas las horas del día (ejemplo de 6:00 AM a 9:00 PM en intervalos de 1 hora)
        $availableHours = [];
        $startHour = 6;
        $endHour = 21;
        
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $timeSlot = sprintf('%02d:00', $hour);
            $endTime = sprintf('%02d:00', $hour + 1);
            
            // Verificar si esta hora está ocupada
            $isOccupied = false;
            $slotInfo = null;
            
            foreach ($occupiedSlots as $occupied) {
                $occupiedStart = Carbon::parse($occupied->start_time)->format('H:i');
                $occupiedEnd = Carbon::parse($occupied->end_time)->format('H:i');
                
                // Verificar si hay conflicto
                if ($timeSlot >= $occupiedStart && $timeSlot < $occupiedEnd) {
                    $isOccupied = true;
                    
                    // Si es grupal y aún hay espacio, está disponible
                    if ($occupied->type === 'grupal' && $occupied->current_participants < $occupied->max_participants) {
                        $isOccupied = false;
                        $slotInfo = [
                            'type' => 'grupal',
                            'available_spots' => $occupied->max_participants - $occupied->current_participants,
                            'max_participants' => $occupied->max_participants,
                        ];
                    }
                    break;
                }
            }
            
            $availableHours[] = [
                'time' => $timeSlot,
                'end_time' => $endTime,
                'available' => !$isOccupied,
                'slot_info' => $slotInfo,
            ];
        }

        return response()->json([
            'professional' => [
                'id' => $professional->id,
                'name' => $professional->name,
                'role' => $professional->role,
                'hourly_rate' => $profile->hourly_rate ?? null,
            ],
            'date' => $request->date,
            'available_hours' => $availableHours,
        ]);
    }
}
