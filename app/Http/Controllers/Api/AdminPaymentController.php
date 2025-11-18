<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\EpaycoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPaymentController extends Controller
{
    protected $epaycoService;

    public function __construct(EpaycoService $epaycoService)
    {
        $this->epaycoService = $epaycoService;
    }

    /**
     * Listar todos los pagos (con filtros opcionales)
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'order']);

        // Filtrar por estado si se proporciona
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por usuario
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtrar por rango de fechas
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Ordenar por más recientes
        $query->orderBy('created_at', 'desc');

        $payments = $query->paginate(20);

        return response()->json([
            'payments' => $payments->items(),
            'pagination' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Actualizar el estado de un pago manualmente
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,cancelled,refunded',
        ]);

        $payment = Payment::findOrFail($id);
        $oldStatus = $payment->status;
        $newStatus = $request->status;

        // Validar transiciones de estado
        if ($oldStatus === 'approved' && $newStatus === 'pending') {
            return response()->json([
                'message' => 'No se puede cambiar un pago aprobado a pendiente',
            ], 400);
        }

        if ($oldStatus === 'refunded' && $newStatus !== 'refunded') {
            return response()->json([
                'message' => 'No se puede cambiar el estado de un pago reembolsado',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Actualizar estado
            $payment->update([
                'status' => $newStatus,
                'meta' => array_merge($payment->meta ?? [], [
                    'manual_status_change' => [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'changed_by' => $request->user()->id,
                        'changed_at' => now()->toDateTimeString(),
                    ],
                ]),
            ]);

            // Si se aprueba manualmente, procesar orden y puntos
            if ($newStatus === 'approved' && $oldStatus !== 'approved' && $payment->order) {
                $order = $payment->order;
                $order->update(['status' => 'completed']);

                // Otorgar puntos si no han sido otorgados
                if (!$order->points_awarded && $order->points_earned > 0) {
                    $payment->user->addPoints(
                        $order->points_earned,
                        'purchase',
                        "Compra aprobada manualmente - Orden #{$order->id}",
                        $order
                    );

                    $order->update(['points_awarded' => true]);
                }
            }

            DB::commit();

            $payment->load(['user', 'order']);

            return response()->json([
                'message' => 'Estado de pago actualizado exitosamente',
                'payment' => $payment,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando estado de pago', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al actualizar el estado del pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reembolsar un pago
     */
    public function refund(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if (!$payment->canBeRefunded()) {
            return response()->json([
                'message' => 'Este pago no puede ser reembolsado',
                'current_status' => $payment->status,
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Intentar reembolso con ePayco
            $refundSuccess = $this->epaycoService->refund($payment->transaction_id);

            if (!$refundSuccess) {
                return response()->json([
                    'message' => 'Error al procesar el reembolso con ePayco',
                ], 500);
            }

            // Actualizar estado del pago
            $payment->update([
                'status' => 'refunded',
                'meta' => array_merge($payment->meta ?? [], [
                    'refund' => [
                        'refunded_by' => $request->user()->id,
                        'refunded_at' => now()->toDateTimeString(),
                    ],
                ]),
            ]);

            // Actualizar estado de la orden
            if ($payment->order) {
                $order = $payment->order;
                $order->update(['status' => 'cancelled']);

                // Revertir puntos si fueron otorgados
                if ($order->points_awarded && $order->points_earned > 0) {
                    $payment->user->subtractPoints(
                        $order->points_earned,
                        'refund',
                        "Reembolso de compra - Orden #{$order->id}",
                        $order
                    );

                    $order->update(['points_awarded' => false]);

                    Log::info('Puntos revertidos por reembolso', [
                        'user_id' => $payment->user_id,
                        'order_id' => $order->id,
                        'points' => $order->points_earned,
                    ]);
                }
            }

            DB::commit();

            $payment->load(['user', 'order']);

            return response()->json([
                'message' => 'Pago reembolsado exitosamente',
                'payment' => $payment,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando reembolso', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al procesar el reembolso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver detalles de un pago específico
     */
    public function show($id)
    {
        $payment = Payment::with(['user', 'order.items.product'])->findOrFail($id);

        return response()->json(['payment' => $payment]);
    }
}
