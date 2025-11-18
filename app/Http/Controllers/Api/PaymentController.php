<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Models\Payment;
use App\Models\Order;
use App\Services\EpaycoService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $epaycoService;

    public function __construct(EpaycoService $epaycoService)
    {
        $this->epaycoService = $epaycoService;
    }

    /**
     * Obtener configuración pública de ePayco
     */
    public function config()
    {
        return response()->json($this->epaycoService->getPublicConfig());
    }

    /**
     * Crear un nuevo pago
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();
        $order = Order::findOrFail($request->order_id);

        // Verificar que la orden pertenece al usuario
        if ($order->user_id !== $user->id) {
            return response()->json([
                'message' => 'No tienes permiso para pagar esta orden',
            ], 403);
        }

        // Verificar si ya existe un pago para esta orden
        $existingPayment = Payment::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingPayment) {
            return response()->json([
                'message' => 'Ya existe un pago para esta orden',
                'payment' => $existingPayment,
            ], 400);
        }

        // Crear el registro de pago en estado pending
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'amount' => $request->amount,
            'currency' => 'COP',
            'status' => 'pending',
            'description' => $request->description ?? "Pago de orden #{$order->id}",
        ]);

        // Construir datos para checkout de ePayco
        $checkoutData = $this->epaycoService->buildCheckout(
            $order->id,
            $request->amount,
            $payment->description,
            $user
        );

        return response()->json([
            'message' => 'Pago creado exitosamente',
            'payment' => $payment,
            'checkout_url' => $checkoutData['checkout_url'],
            'invoice' => $checkoutData['invoice'],
        ], 201);
    }

    /**
     * Página de respuesta después del pago (opcional)
     */
    public function response(Request $request)
    {
        // Esta es la URL de retorno después del pago
        // Puedes redirigir a una página web o simplemente mostrar un mensaje
        
        $refPayco = $request->get('ref_payco');
        $transactionId = $request->get('transaction_id');
        
        return response()->json([
            'message' => 'Pago procesado',
            'ref_payco' => $refPayco,
            'transaction_id' => $transactionId,
            'status' => $request->get('x_response'),
        ]);
    }

    /**
     * Mostrar un pago específico
     */
    public function show($id)
    {
        $payment = Payment::with('order')->findOrFail($id);
        
        // Verificar que el pago pertenece al usuario
        if ($payment->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'No tienes permiso para ver este pago',
            ], 403);
        }

        return response()->json($payment);
    }

    /**
     * TEMPORAL: Simular aprobación de pago (solo para pruebas sin ePayco)
     */
    public function simulateApproval($id)
    {
        $payment = Payment::findOrFail($id);
        
        // Verificar que el pago pertenece al usuario
        if ($payment->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'No tienes permiso',
            ], 403);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'message' => 'El pago ya fue procesado',
                'status' => $payment->status,
            ], 400);
        }

        // Simular datos del webhook de ePayco
        $webhookData = [
            'x_ref_payco' => 'TEST-' . time(),
            'x_transaction_id' => 'TXN-' . time(),
            'x_amount' => $payment->amount,
            'x_currency_code' => 'COP',
            'x_response' => 'Aceptada',
            'x_cod_response' => 1,
            'x_extra1' => $payment->order_id,
            'x_extra2' => $payment->user_id,
        ];

        // Procesar como webhook
        app(PaymentWebhookController::class)->handle(
            new \Illuminate\Http\Request($webhookData)
        );

        return response()->json([
            'message' => 'Pago simulado como aprobado',
            'payment' => $payment->fresh(),
        ]);
    }
}
