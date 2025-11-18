<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Crear o actualizar review para un profesional
     */
    public function store(Request $request, $professionalId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $professional = User::findOrFail($professionalId);

        // Verificar que sea entrenador o nutricionista
        if (!$professional->hasAnyRole(['entrenador', 'nutricionista'])) {
            return response()->json([
                'message' => 'Solo se puede calificar a entrenadores y nutricionistas',
            ], 400);
        }

        // Crear o actualizar la review
        $review = Review::updateOrCreate(
            [
                'user_id' => $professionalId,
                'client_id' => auth()->id(),
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return response()->json([
            'message' => 'Calificación guardada exitosamente',
            'review' => $review,
        ], 201);
    }

    /**
     * Obtener reviews de un profesional
     */
    public function index($professionalId)
    {
        $reviews = Review::where('user_id', $professionalId)
            ->with('client:id,name')
            ->latest()
            ->get();

        $avgRating = $reviews->avg('rating');

        return response()->json([
            'reviews' => $reviews,
            'average_rating' => round($avgRating, 1),
            'total_reviews' => $reviews->count(),
        ]);
    }

    /**
     * Eliminar una review propia
     */
    public function destroy($professionalId)
    {
        $review = Review::where('user_id', $professionalId)
            ->where('client_id', auth()->id())
            ->firstOrFail();
            
        $review->delete();

        return response()->json([
            'message' => 'Calificación eliminada exitosamente',
        ]);
    }
}
