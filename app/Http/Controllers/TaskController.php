<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tasks = $request->user()->tasks()->latest()->get();
        
        return response()->json([
            'tasks' => $tasks,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
        ]);

        $task = $request->user()->tasks()->create($validated);

        return response()->json([
            'message' => 'Tarea creada exitosamente',
            'task' => $task,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        // Verificar que la tarea pertenece al usuario autenticado
        if ($task->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No autorizado',
            ], 403);
        }

        return response()->json([
            'task' => $task,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        // Verificar que la tarea pertenece al usuario autenticado
        if ($task->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No autorizado',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Tarea actualizada exitosamente',
            'task' => $task,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        // Verificar que la tarea pertenece al usuario autenticado
        if ($task->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No autorizado',
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Tarea eliminada exitosamente',
        ]);
    }
}
