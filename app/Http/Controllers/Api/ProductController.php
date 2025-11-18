<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Filtrar por categoría
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filtrar por estado activo (para clientes)
        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        // Filtrar por productos destacados
        if ($request->has('featured')) {
            $query->where('is_featured', true);
        }

        // Buscar por nombre
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(20);

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Producto creado exitosamente',
            'product' => $product->load('category'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json([
            'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku,' . $id,
            'images' => 'nullable|array',
            'images.*' => 'string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Producto actualizado exitosamente',
            'product' => $product->load('category'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente',
        ]);
    }

    /**
     * Update stock
     */
    public function updateStock(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|integer',
            'operation' => 'required|in:add,subtract,set',
        ]);

        switch ($validated['operation']) {
            case 'add':
                $product->incrementStock($validated['quantity']);
                break;
            case 'subtract':
                if ($product->stock < $validated['quantity']) {
                    return response()->json([
                        'message' => 'Stock insuficiente',
                    ], 400);
                }
                $product->decrementStock($validated['quantity']);
                break;
            case 'set':
                $product->stock = $validated['quantity'];
                $product->save();
                break;
        }

        $product->refresh();

        return response()->json([
            'message' => 'Stock actualizado exitosamente',
            'product' => $product,
        ]);
    }

    /**
     * Get product history
     */
    public function history(string $id)
    {
        $product = Product::findOrFail($id);
        $history = $product->history()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($history);
    }
}
