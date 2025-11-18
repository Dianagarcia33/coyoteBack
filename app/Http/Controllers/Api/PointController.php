<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PointController extends Controller
{
    /**
     * Obtener el balance de puntos del usuario autenticado
     */
    public function balance(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'points' => $user->points,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Obtener el historial de transacciones de puntos
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $transactions = $user->pointTransactions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return response()->json($transactions);
    }

    /**
     * Obtener las reglas de cómo ganar puntos
     */
    public function rules()
    {
        return response()->json([
            'rules' => [
                [
                    'source' => 'purchase',
                    'title' => 'Compra de productos',
                    'description' => 'Gana 1 punto por cada $10 COP gastados en productos',
                    'points_rate' => '1 punto = $10 COP'
                ],
                [
                    'source' => 'referral',
                    'title' => 'Referir amigos',
                    'description' => 'Gana puntos cuando tus amigos se registren y hagan su primera compra',
                    'points_rate' => '50 puntos por referido'
                ],
                [
                    'source' => 'achievement',
                    'title' => 'Logros',
                    'description' => 'Completa objetivos y desafíos en la app',
                    'points_rate' => 'Variable según el logro'
                ],
                [
                    'source' => 'bonus',
                    'title' => 'Bonos especiales',
                    'description' => 'Puntos extra por eventos especiales y promociones',
                    'points_rate' => 'Variable'
                ]
            ],
            'redemption' => [
                'description' => 'Los puntos pueden canjearse por descuentos en futuras compras',
                'minimum' => 100,
                'rate' => '100 puntos = $10 COP de descuento'
            ]
        ]);
    }

    /**
     * Agregar puntos a un usuario (solo admin)
     */
    public function addPoints(Request $request, $userId)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $user = \App\Models\User::findOrFail($userId);
        
        $user->addPoints(
            $request->points,
            'admin',
            $request->description ?? 'Puntos agregados por administrador'
        );

        return response()->json([
            'message' => 'Puntos agregados exitosamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'points' => $user->points,
            ]
        ]);
    }

    /**
     * Restar puntos a un usuario (solo admin)
     */
    public function subtractPoints(Request $request, $userId)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $user = \App\Models\User::findOrFail($userId);
        
        $success = $user->subtractPoints(
            $request->points,
            'admin',
            $request->description ?? 'Puntos restados por administrador'
        );

        if (!$success) {
            return response()->json([
                'message' => 'El usuario no tiene suficientes puntos',
            ], 400);
        }

        return response()->json([
            'message' => 'Puntos restados exitosamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'points' => $user->points,
            ]
        ]);
    }
}
