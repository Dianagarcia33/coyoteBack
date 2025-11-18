<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
    /**
     * Listar retiros del usuario autenticado
     */
    public function index(Request $request)
    {
        $withdrawals = Withdrawal::whereHas('wallet', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->with('bankAccount')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($withdrawals);
    }

    /**
     * Solicitar un nuevo retiro
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000', // Mínimo 10,000 COP
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $wallet = $user->wallet ?? $user->getOrCreateWallet();

        // Verificar que la cuenta bancaria pertenece al usuario
        $bankAccount = $user->bankAccounts()->findOrFail($request->bank_account_id);

        if (!$bankAccount->is_active) {
            return response()->json([
                'message' => 'La cuenta bancaria no está activa',
            ], 400);
        }

        // Verificar saldo disponible
        if ($wallet->balance_available < $request->amount) {
            return response()->json([
                'message' => 'Saldo insuficiente',
                'available' => $wallet->balance_available,
                'requested' => $request->amount,
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Crear solicitud de retiro
            $withdrawal = $wallet->withdrawals()->create([
                'bank_account_id' => $request->bank_account_id,
                'amount' => $request->amount,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Restar del balance disponible
            $wallet->subtractAvailableBalance(
                $request->amount,
                "Retiro solicitado - #{$withdrawal->id}",
                $withdrawal
            );

            DB::commit();

            $withdrawal->load('bankAccount');

            return response()->json([
                'message' => 'Solicitud de retiro creada exitosamente',
                'withdrawal' => $withdrawal,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando retiro', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al procesar el retiro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver detalles de un retiro
     */
    public function show(Request $request, $id)
    {
        $withdrawal = Withdrawal::whereHas('wallet', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->with('bankAccount')
            ->findOrFail($id);

        return response()->json(['withdrawal' => $withdrawal]);
    }

    /**
     * Cancelar un retiro (solo si está pendiente)
     */
    public function cancel(Request $request, $id)
    {
        $withdrawal = Withdrawal::whereHas('wallet', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->findOrFail($id);

        if (!$withdrawal->canBeCancelled()) {
            return response()->json([
                'message' => 'Este retiro no puede ser cancelado',
                'current_status' => $withdrawal->status,
            ], 400);
        }

        DB::beginTransaction();

        try {
            $withdrawal->update(['status' => 'cancelled']);

            // Devolver dinero al balance disponible
            $wallet = $withdrawal->wallet;
            $wallet->increment('balance_available', $withdrawal->amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $withdrawal->amount,
                'balance_type' => 'available',
                'description' => "Retiro cancelado - #{$withdrawal->id}",
                'reference_id' => $withdrawal->id,
                'reference_type' => get_class($withdrawal),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Retiro cancelado exitosamente',
                'withdrawal' => $withdrawal,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cancelar el retiro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========== MÉTODOS ADMIN ==========

    /**
     * Listar todos los retiros (Admin)
     */
    public function adminIndex(Request $request)
    {
        $query = Withdrawal::with(['wallet.user', 'bankAccount']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($withdrawals);
    }

    /**
     * Aprobar retiro (Admin)
     */
    public function approve(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if (!$withdrawal->isPending()) {
            return response()->json([
                'message' => 'Solo se pueden aprobar retiros pendientes',
                'current_status' => $withdrawal->status,
            ], 400);
        }

        $withdrawal->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Retiro aprobado',
            'withdrawal' => $withdrawal,
        ]);
    }

    /**
     * Marcar retiro como completado (Admin)
     */
    public function complete(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if (!$withdrawal->isApproved()) {
            return response()->json([
                'message' => 'El retiro debe estar aprobado para completarlo',
            ], 400);
        }

        $withdrawal->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Retiro marcado como completado',
            'withdrawal' => $withdrawal,
        ]);
    }

    /**
     * Rechazar retiro (Admin)
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string',
        ]);

        $withdrawal = Withdrawal::findOrFail($id);

        if (!$withdrawal->isPending()) {
            return response()->json([
                'message' => 'Solo se pueden rechazar retiros pendientes',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $withdrawal->update([
                'status' => 'rejected',
                'notes' => $request->notes,
            ]);

            // Devolver dinero al balance disponible
            $wallet = $withdrawal->wallet;
            $wallet->increment('balance_available', $withdrawal->amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $withdrawal->amount,
                'balance_type' => 'available',
                'description' => "Retiro rechazado - #{$withdrawal->id}: {$request->notes}",
                'reference_id' => $withdrawal->id,
                'reference_type' => get_class($withdrawal),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Retiro rechazado',
                'withdrawal' => $withdrawal,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al rechazar el retiro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
