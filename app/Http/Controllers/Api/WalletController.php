<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Obtener balance de la billetera del usuario autenticado
     */
    public function balance(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet ?? $user->getOrCreateWallet();

        return response()->json([
            'wallet' => [
                'balance_available' => $wallet->balance_available,
                'balance_frozen' => $wallet->balance_frozen,
                'total_balance' => $wallet->total_balance,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Obtener historial de transacciones de la billetera
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'transactions' => [],
                'message' => 'No hay transacciones'
            ]);
        }

        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }
}
