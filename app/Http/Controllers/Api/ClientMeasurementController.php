<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientMeasurement;
use Illuminate\Http\Request;

class ClientMeasurementController extends Controller
{
    /**
     * Listar todas las medidas del cliente
     */
    public function index()
    {
        $measurements = auth()->user()->measurements()
            ->orderBy('measured_at', 'desc')
            ->get();
        
        return response()->json($measurements);
    }

    /**
     * Registrar nueva medida
     */
    public function store(Request $request)
    {
        $request->validate([
            'weight' => 'nullable|numeric|min:0',
            'body_fat' => 'nullable|numeric|min:0|max:100',
            'muscle_mass' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'measured_at' => 'nullable|date',
        ]);

        $measurement = auth()->user()->measurements()->create([
            'weight' => $request->weight,
            'body_fat' => $request->body_fat,
            'muscle_mass' => $request->muscle_mass,
            'notes' => $request->notes,
            'measured_at' => $request->measured_at ?? now(),
        ]);

        // Actualizar el peso en el perfil del cliente si se proporciona
        if ($request->weight) {
            auth()->user()->clientProfile()->update([
                'weight' => $request->weight,
            ]);
        }

        return response()->json([
            'message' => 'Medida registrada exitosamente',
            'measurement' => $measurement,
        ], 201);
    }

    /**
     * Obtener progreso (comparar última medida con la primera)
     */
    public function progress()
    {
        $first = auth()->user()->measurements()
            ->orderBy('measured_at', 'asc')
            ->first();
            
        $latest = auth()->user()->measurements()
            ->orderBy('measured_at', 'desc')
            ->first();

        if (!$first || !$latest) {
            return response()->json([
                'message' => 'No hay suficientes medidas para calcular progreso',
            ]);
        }

        $progress = [
            'weight_change' => $latest->weight - $first->weight,
            'body_fat_change' => $latest->body_fat - $first->body_fat,
            'muscle_mass_change' => $latest->muscle_mass - $first->muscle_mass,
            'first_measurement' => $first,
            'latest_measurement' => $latest,
        ];

        return response()->json($progress);
    }

    /**
     * Eliminar una medida
     */
    public function destroy($id)
    {
        $measurement = ClientMeasurement::where('client_id', auth()->id())->findOrFail($id);
        $measurement->delete();

        return response()->json([
            'message' => 'Medida eliminada exitosamente',
        ]);
    }
}
