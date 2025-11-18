<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SessionPackage;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionPackageController extends Controller
{
    /**
     * Listar paquetes del cliente autenticado
     */
    public function myPackages(Request $request)
    {
        $packages = $request->user()
            ->purchasedPackages()
            ->with('professional')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['packages' => $packages]);
    }

    /**
     * Listar paquetes del profesional autenticado
     */
    public function professionalPackages(Request $request)
    {
        $packages = $request->user()
            ->offeredPackages()
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['packages' => $packages]);
    }

    /**
     * Ver detalles de un paquete con sesiones
     */
    public function show($id)
    {
        $package = SessionPackage::with(['client', 'professional', 'trainingSessions'])
            ->findOrFail($id);

        return response()->json(['package' => $package]);
    }

    /**
     * Completar una sesión (solo profesional)
     */
    public function completeSession(Request $request, $sessionId)
    {
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $session = TrainingSession::findOrFail($sessionId);
        $package = $session->sessionPackage;

        // Verificar que el profesional autenticado es el de la sesión
        if ($session->professional_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No tienes permiso para completar esta sesión',
            ], 403);
        }

        if (!$session->canBeCompleted()) {
            return response()->json([
                'message' => 'Esta sesión no puede ser completada',
                'current_status' => $session->status,
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Marcar sesión como completada
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'notes' => $request->notes,
            ]);

            // Incrementar sesiones completadas del paquete
            $package->increment('completed_sessions');

            // Calcular el monto de esta sesión
            $sessionAmount = $package->price_per_session;

            // Mover dinero de congelado a disponible en la billetera del profesional
            $professional = $session->professional;
            $wallet = $professional->getOrCreateWallet();

            $wallet->moveFrozenToAvailable(
                $sessionAmount,
                'session_completed',
                "Sesión completada - Cliente: {$session->client->name}",
                $session
            );

            // Si todas las sesiones están completadas, marcar paquete como completado
            if ($package->isCompleted()) {
                $package->update(['status' => 'completed']);
            }

            DB::commit();

            $session->load(['sessionPackage', 'client']);

            Log::info('Sesión completada y dinero liberado', [
                'session_id' => $session->id,
                'professional_id' => $professional->id,
                'amount' => $sessionAmount,
            ]);

            return response()->json([
                'message' => 'Sesión completada exitosamente',
                'session' => $session,
                'amount_released' => $sessionAmount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completando sesión', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al completar la sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar sesiones del profesional
     */
    public function professionalSessions(Request $request)
    {
        $sessions = $request->user()
            ->professionalSessions()
            ->with(['client', 'sessionPackage'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return response()->json($sessions);
    }

    /**
     * Listar sesiones del cliente
     */
    public function clientSessions(Request $request)
    {
        $sessions = $request->user()
            ->clientSessions()
            ->with(['professional', 'sessionPackage'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return response()->json($sessions);
    }
}

