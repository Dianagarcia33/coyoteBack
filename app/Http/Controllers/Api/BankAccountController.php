<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    /**
     * Listar cuentas bancarias del usuario
     */
    public function index(Request $request)
    {
        $accounts = $request->user()
            ->bankAccounts()
            ->where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->get();

        return response()->json(['bank_accounts' => $accounts]);
    }

    /**
     * Agregar nueva cuenta bancaria
     */
    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_type' => 'required|in:ahorros,corriente',
            'account_number' => 'required|string|max:50',
            'account_holder_name' => 'required|string|max:255',
            'document_type' => 'required|in:CC,CE,NIT,Pasaporte',
            'document_number' => 'required|string|max:50',
            'is_primary' => 'boolean',
        ]);

        $user = $request->user();

        // Si es cuenta primaria, desmarcar las demás
        if ($request->is_primary) {
            $user->bankAccounts()->update(['is_primary' => false]);
        }

        // Si no tiene cuentas, esta será la primaria
        $isPrimary = $request->is_primary ?? !$user->bankAccounts()->exists();

        $account = $user->bankAccounts()->create([
            'bank_name' => $request->bank_name,
            'account_type' => $request->account_type,
            'account_number' => $request->account_number,
            'account_holder_name' => $request->account_holder_name,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'is_primary' => $isPrimary,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Cuenta bancaria agregada exitosamente',
            'bank_account' => $account,
        ], 201);
    }

    /**
     * Actualizar cuenta bancaria
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'bank_name' => 'string|max:255',
            'account_type' => 'in:ahorros,corriente',
            'account_number' => 'string|max:50',
            'account_holder_name' => 'string|max:255',
            'document_type' => 'in:CC,CE,NIT,Pasaporte',
            'document_number' => 'string|max:50',
            'is_primary' => 'boolean',
        ]);

        $account = $request->user()->bankAccounts()->findOrFail($id);

        // Si se marca como primaria, desmarcar las demás
        if ($request->is_primary) {
            $request->user()->bankAccounts()->update(['is_primary' => false]);
        }

        $account->update($request->only([
            'bank_name',
            'account_type',
            'account_number',
            'account_holder_name',
            'document_type',
            'document_number',
            'is_primary',
        ]));

        return response()->json([
            'message' => 'Cuenta bancaria actualizada',
            'bank_account' => $account,
        ]);
    }

    /**
     * Eliminar (desactivar) cuenta bancaria
     */
    public function destroy(Request $request, $id)
    {
        $account = $request->user()->bankAccounts()->findOrFail($id);
        
        $account->update(['is_active' => false]);

        return response()->json([
            'message' => 'Cuenta bancaria desactivada',
        ]);
    }

    /**
     * Marcar como cuenta primaria
     */
    public function setPrimary(Request $request, $id)
    {
        $user = $request->user();
        $account = $user->bankAccounts()->findOrFail($id);

        // Desmarcar todas las cuentas primarias
        $user->bankAccounts()->update(['is_primary' => false]);

        // Marcar esta como primaria
        $account->update(['is_primary' => true]);

        return response()->json([
            'message' => 'Cuenta marcada como primaria',
            'bank_account' => $account,
        ]);
    }
}
