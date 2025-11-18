<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::withCount('products');

        // Filtrar solo activas (para clientes)
        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $categories = $query->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Categoría creada exitosamente',
            'category' => $category,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::withCount('products')
            ->with(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->findOrFail($id);

        return response()->json([
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Categoría actualizada exitosamente',
            'category' => $category,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría con productos asociados',
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente',
        ]);
    }
}
