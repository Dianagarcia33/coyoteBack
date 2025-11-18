<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Services\EpaycoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected $epaycoService;

    public function __construct(EpaycoService $epaycoService)
    {
        $this->epaycoService = $epaycoService;
    }

    /**
     * Manejar webhook de confirmación de ePayco
     */
    public function handle(Request $request)
    {
        // Registrar el webhook completo
        Log::info('ePayco webhook recibido', $request->all());

        try {
            // Verificar firma de seguridad
            if (!$this->epaycoService->verifySignature($request->all())) {
                Log::error('ePayco webhook con firma inválida', $request->all());
                return response()->json(['error' => 'Firma inválida'], 400);
            }

            $xRefPayco = $request->get('x_ref_payco');
            $xTransactionId = $request->get('x_transaction_id');
            $xAmount = $request->get('x_amount');
            $xResponse = $request->get('x_response');
            $xCodResponse = $request->get('x_cod_response');
            $xApprovalCode = $request->get('x_approval_code');
            $xPaymentMethod = $request->get('x_franchise');
            $extra1 = $request->get('x_extra1'); // order_id

            // Buscar el pago por referencia de ePayco o transaction_id
            $payment = Payment::where('epayco_ref', $xRefPayco)
                ->orWhere('transaction_id', $xTransactionId)
                ->first();

            // Si no existe, intentar buscar por order_id
            if (!$payment && $extra1) {
                $payment = Payment::where('order_id', $extra1)
                    ->whereNull('epayco_ref')
                    ->first();
            }

            if (!$payment) {
                Log::error('Pago no encontrado para webhook', [
                    'x_ref_payco' => $xRefPayco,
                    'x_transaction_id' => $xTransactionId,
                    'x_extra1' => $extra1,
                ]);
                return response()->json(['error' => 'Pago no encontrado'], 404);
            }

            // Idempotencia: si ya está aprobado, no procesar de nuevo
            if ($payment->isApproved()) {
                Log::info('Pago ya estaba aprobado, ignorando webhook duplicado', [
                    'payment_id' => $payment->id,
                ]);
                return response()->json(['status' => 'ok'], 200);
            }

            DB::beginTransaction();

            // Mapear estado
            $newStatus = $this->epaycoService->mapStatus($xResponse, $xCodResponse);

            // Actualizar el pago
            $payment->update([
                'status' => $newStatus,
                'transaction_id' => $xTransactionId,
                'epayco_ref' => $xRefPayco,
                'payment_method' => $xPaymentMethod,
                'meta' => array_merge($payment->meta ?? [], [
                    'x_response' => $xResponse,
                    'x_cod_response' => $xCodResponse,
                    'x_approval_code' => $xApprovalCode,
                    'webhook_received_at' => now()->toDateTimeString(),
                ]),
            ]);

            // Si el pago fue aprobado, procesar orden/paquete y puntos
            if ($newStatus === 'approved') {
                if ($payment->order) {
                    $this->processApprovedOrderPayment($payment);
                }
                
                // Verificar si es pago de un paquete de sesiones
                $sessionPackage = \App\Models\SessionPackage::where('payment_id', $payment->id)->first();
                if ($sessionPackage) {
                    $this->processApprovedSessionPayment($payment, $sessionPackage);
                }
                
                // Verificar si es pago de una reserva (booking)
                $booking = Booking::where('payment_id', $payment->id)->first();
                if ($booking) {
                    $this->processApprovedBookingPayment($payment, $booking);
                }
            }

            DB::commit();

            Log::info('Webhook procesado exitosamente', [
                'payment_id' => $payment->id,
                'status' => $newStatus,
            ]);

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando webhook de ePayco', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);
            
            return response()->json(['error' => 'Error interno'], 500);
        }
    }

    /**
     * Procesar pago aprobado de orden (productos): actualizar orden y otorgar puntos
     * El dinero va directo al ADMIN (balance disponible)
     */
    protected function processApprovedOrderPayment(Payment $payment)
    {
        $order = $payment->order;
        
        // Actualizar estado de la orden
        $order->update(['status' => 'completed']);

        // Otorgar puntos si aún no han sido otorgados
        if (!$order->points_awarded && $order->points_earned > 0) {
            $payment->user->addPoints(
                $order->points_earned,
                'purchase',
                "Compra aprobada - Orden #{$order->id}",
                $order
            );

            $order->update(['points_awarded' => true]);

            Log::info('Puntos otorgados por pago aprobado', [
                'user_id' => $payment->user_id,
                'order_id' => $order->id,
                'points' => $order->points_earned,
            ]);
        }

        // Agregar dinero a la billetera del ADMIN (directo a disponible)
        $admin = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'admin');
        })->first();

        if ($admin) {
            $adminWallet = $admin->getOrCreateWallet();
            $adminWallet->addAvailableBalance(
                $payment->amount,
                "Venta de productos - Orden #{$order->id}",
                $order
            );

            Log::info('Pago de productos agregado a billetera admin', [
                'admin_id' => $admin->id,
                'amount' => $payment->amount,
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * Procesar pago aprobado de sesiones: activar paquete y congelar dinero
     * El dinero va al PROFESIONAL (balance congelado hasta completar sesiones)
     */
    protected function processApprovedSessionPayment(Payment $payment, $sessionPackage)
    {
        // Activar el paquete de sesiones
        $sessionPackage->update(['status' => 'active']);

        // Agregar dinero a la billetera del PROFESIONAL (balance congelado)
        $professional = $sessionPackage->professional;
        $professionalWallet = $professional->getOrCreateWallet();
        
        $professionalWallet->addFrozenBalance(
            $payment->amount,
            'payment_received',
            "Pago de paquete de sesiones - Cliente: {$payment->user->name}",
            $sessionPackage
        );

        Log::info('Pago de sesiones agregado a billetera del profesional (congelado)', [
            'professional_id' => $professional->id,
            'amount' => $payment->amount,
            'session_package_id' => $sessionPackage->id,
            'client_id' => $payment->user_id,
        ]);
    }

    /**
     * Procesar pago aprobado de booking: actualizar slot y congelar dinero
     * El dinero va al PROFESIONAL (balance congelado hasta que confirme y complete la sesión)
     */
    protected function processApprovedBookingPayment(Payment $payment, Booking $booking)
    {
        $slot = $booking->availableSlot;
        
        // Incrementar participantes en el slot
        $slot->increment('current_participants');
        
        // Si el slot está lleno, actualizar su estado
        if ($slot->current_participants >= $slot->max_participants) {
            $slot->update(['status' => 'full']);
        }
        
        // Congelar dinero en la billetera del PROFESIONAL
        $professional = $slot->user; // El profesional dueño del slot
        $professionalWallet = $professional->getOrCreateWallet();
        
        $professionalWallet->addFrozenBalance(
            $payment->amount,
            'booking',
            "Reserva de sesión - Cliente: {$payment->user->name} - {$slot->start_time->format('d/m/Y H:i')}",
            $booking
        );

        Log::info('Pago de reserva procesado - dinero congelado en billetera del profesional', [
            'professional_id' => $professional->id,
            'amount' => $payment->amount,
            'booking_id' => $booking->id,
            'slot_id' => $slot->id,
            'client_id' => $payment->user_id,
            'current_participants' => $slot->current_participants,
            'max_participants' => $slot->max_participants,
        ]);
    }
}
