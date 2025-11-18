<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    /**
     * Subir imágenes para un producto
     */
    public function upload(Request $request, $productId)
    {
        $request->validate([
            'images' => 'required|array|max:5', // Máximo 5 imágenes
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max por imagen
        ]);

        $product = Product::findOrFail($productId);
        $uploadedUrls = [];

        foreach ($request->file('images') as $image) {
            // Generar nombre único
            $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
            
            // Guardar en storage/app/public/products
            $path = $image->storeAs('products', $filename, 'public');
            
            // Generar URL pública
            $url = asset('storage/' . $path);
            $uploadedUrls[] = $url;
        }

        // Agregar URLs al array de imágenes del producto
        $currentImages = $product->images ?? [];
        $newImages = array_merge($currentImages, $uploadedUrls);
        
        $product->update(['images' => $newImages]);

        return response()->json([
            'message' => 'Imágenes subidas exitosamente',
            'uploaded_images' => $uploadedUrls,
            'total_images' => count($newImages),
        ], 201);
    }

    /**
     * Eliminar una imagen específica
     */
    public function delete(Request $request, $productId)
    {
        $request->validate([
            'image_url' => 'required|string',
        ]);

        $product = Product::findOrFail($productId);
        $imageUrl = $request->image_url;

        // Buscar la URL en el array de imágenes
        $currentImages = $product->images ?? [];
        
        if (!in_array($imageUrl, $currentImages)) {
            return response()->json([
                'message' => 'Imagen no encontrada en este producto',
            ], 404);
        }

        // Eliminar archivo físico del storage
        $this->deleteImageFile($imageUrl);

        // Eliminar URL del array
        $newImages = array_values(array_filter($currentImages, function($url) use ($imageUrl) {
            return $url !== $imageUrl;
        }));

        $product->update(['images' => $newImages]);

        return response()->json([
            'message' => 'Imagen eliminada exitosamente',
            'remaining_images' => count($newImages),
        ]);
    }

    /**
     * Reemplazar todas las imágenes del producto
     */
    public function replace(Request $request, $productId)
    {
        $request->validate([
            'images' => 'required|array|max:5',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $product = Product::findOrFail($productId);

        // Eliminar imágenes antiguas
        $oldImages = $product->images ?? [];
        foreach ($oldImages as $oldImageUrl) {
            $this->deleteImageFile($oldImageUrl);
        }

        // Subir nuevas imágenes
        $newUrls = [];
        foreach ($request->file('images') as $image) {
            $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('products', $filename, 'public');
            $newUrls[] = asset('storage/' . $path);
        }

        $product->update(['images' => $newUrls]);

        return response()->json([
            'message' => 'Imágenes reemplazadas exitosamente',
            'images' => $newUrls,
        ]);
    }

    /**
     * Eliminar archivo físico del storage
     */
    protected function deleteImageFile($imageUrl)
    {
        // Extraer el path relativo de la URL
        // De: http://localhost:8000/storage/products/uuid.jpg
        // A: products/uuid.jpg
        $relativePath = str_replace(asset('storage/'), '', $imageUrl);
        
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}

