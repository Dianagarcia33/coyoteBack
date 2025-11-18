<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientGoal;
use Illuminate\Http\Request;

class ClientGoalController extends Controller
{
    /**
     * Listar todas las metas del cliente
     */
    public function index()
    {
        $goals = auth()->user()->goals()->latest()->get();
        
        return response()->json($goals);
    }

    /**
     * Crear una nueva meta
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_weight' => 'nullable|numeric|min:0',
            'target_metric' => 'nullable|string',
            'target_value' => 'nullable|numeric',
            'target_date' => 'nullable|date',
        ]);

        $goal = auth()->user()->goals()->create([
            'title' => $request->title,
            'description' => $request->description,
            'target_weight' => $request->target_weight,
            'target_metric' => $request->target_metric,
            'target_value' => $request->target_value,
            'start_date' => now(),
            'target_date' => $request->target_date,
            'status' => 'active',
            'progress' => 0,
        ]);

        return response()->json([
            'message' => 'Meta creada exitosamente',
            'goal' => $goal,
        ], 201);
    }

    /**
     * Actualizar una meta
     */
    public function update(Request $request, $id)
    {
        $goal = ClientGoal::where('client_id', auth()->id())->findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'target_weight' => 'nullable|numeric|min:0',
            'target_metric' => 'nullable|string',
            'target_value' => 'nullable|numeric',
            'target_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|in:active,completed,cancelled',
        ]);

        $goal->update($request->all());

        return response()->json([
            'message' => 'Meta actualizada exitosamente',
            'goal' => $goal,
        ]);
    }

    /**
     * Marcar meta como completada
     */
    public function complete($id)
    {
        $goal = ClientGoal::where('client_id', auth()->id())->findOrFail($id);
        
        $goal->update([
            'status' => 'completed',
            'progress' => 100,
        ]);

        return response()->json([
            'message' => 'Meta completada',
            'goal' => $goal,
        ]);
    }

    /**
     * Eliminar una meta
     */
    public function destroy($id)
    {
        $goal = ClientGoal::where('client_id', auth()->id())->findOrFail($id);
        $goal->delete();

        return response()->json([
            'message' => 'Meta eliminada exitosamente',
        ]);
    }
}
