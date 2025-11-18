<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EpaycoService
{
    protected $publicKey;
    protected $privateKey;
    protected $pCustIdCliente;
    protected $pKey;
    protected $test;
    protected $currency;
    protected $baseUrl;

    public function __construct()
    {
        $this->publicKey = config('services.epayco.public_key');
        $this->privateKey = config('services.epayco.private_key');
        $this->pCustIdCliente = config('services.epayco.p_cust_id_cliente');
        $this->pKey = config('services.epayco.p_key');
        $this->test = config('services.epayco.test');
        $this->currency = config('services.epayco.currency');
        $this->baseUrl = config('services.epayco.base_url');
    }

    /**
     * Construir payload para checkout de ePayco
     * Genera una URL de checkout usando los parámetros
     */
    public function buildCheckout($orderId, $amount, $description, $user)
    {
        $invoiceNumber = 'ORD-' . $orderId . '-' . time();

        $params = [
            'public-key' => $this->publicKey,
            'name' => $description,
            'description' => $description,
            'invoice' => $invoiceNumber,
            'currency' => $this->currency,
            'amount' => (string) $amount,
            'tax_base' => '0',
            'tax' => '0',
            'country' => 'co',
            'lang' => 'es',
            'external' => 'false',
            
            // Extras para referencia
            'extra1' => (string) $orderId,
            'extra2' => (string) $user->id,
            'extra3' => '',
            
            // URLs de respuesta
            'response' => url('/api/payments/response'),
            'confirmation' => url('/api/webhooks/epayco'),
            
            // Datos del cliente
            'name_billing' => $user->name,
            'email_billing' => $user->email,
            
            // Modo test
            'test' => $this->test ? 'true' : 'false',
        ];

        // Construir URL con parámetros
        $checkoutUrl = 'https://checkout.epayco.co/checkout.php?' . http_build_query($params);

        return [
            'success' => true,
            'checkout_url' => $checkoutUrl,
            'invoice' => $invoiceNumber,
            'amount' => $amount,
            'params' => $params,
        ];
    }

    /**
     * Verificar la firma de ePayco
     * Fórmula: md5(p_cust_id_cliente^p_key^x_ref_payco^x_transaction_id^x_amount^x_currency_code)
     */
    public function verifySignature(array $payload): bool
    {
        if (!isset($payload['x_signature'])) {
            Log::warning('ePayco webhook sin x_signature', $payload);
            return false;
        }

        $xRefPayco = $payload['x_ref_payco'] ?? '';
        $xTransactionId = $payload['x_transaction_id'] ?? '';
        $xAmount = $payload['x_amount'] ?? '';
        $xCurrencyCode = $payload['x_currency_code'] ?? '';

        $signatureString = $this->pCustIdCliente . '^' . 
                          $this->pKey . '^' . 
                          $xRefPayco . '^' . 
                          $xTransactionId . '^' . 
                          $xAmount . '^' . 
                          $xCurrencyCode;

        $calculatedSignature = md5($signatureString);

        $isValid = $calculatedSignature === $payload['x_signature'];

        if (!$isValid) {
            Log::warning('ePayco signature mismatch', [
                'expected' => $calculatedSignature,
                'received' => $payload['x_signature'],
                'string' => $signatureString,
            ]);
        }

        return $isValid;
    }

    /**
     * Mapear el estado de respuesta de ePayco a nuestros estados
     */
    public function mapStatus($xResponse, $xCodResponse): string
    {
        // x_response puede ser: Aceptada, Rechazada, Pendiente, Fallida
        // x_cod_response: 1 = Aceptada, 2 = Rechazada, 3 = Pendiente, 4 = Fallida
        
        if ($xCodResponse == 1 || strtolower($xResponse) === 'aceptada') {
            return 'approved';
        }
        
        if ($xCodResponse == 2 || strtolower($xResponse) === 'rechazada') {
            return 'rejected';
        }
        
        if ($xCodResponse == 4 || strtolower($xResponse) === 'fallida') {
            return 'rejected';
        }
        
        return 'pending';
    }

    /**
     * Solicitar reembolso a ePayco
     * Nota: ePayco requiere que el reembolso se gestione desde su panel
     * Este método registra la intención de reembolso
     */
    public function refund($transactionId)
    {
        // ePayco no tiene API pública de reembolso en todos los planes
        // En producción, esto debería registrarse y gestionarse manualmente
        // o usar la API de reembolsos si está disponible en tu plan
        
        Log::info('Solicitud de reembolso para transacción: ' . $transactionId, [
            'transaction_id' => $transactionId,
            'timestamp' => now(),
        ]);

        // Si tienes acceso a la API de reembolsos:
        /*
        try {
            $response = Http::withBasicAuth($this->privateKey, '')
                ->post($this->baseUrl . '/payment/rest/v1/charge/' . $transactionId . '/refund');
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error en reembolso ePayco', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            return false;
        }
        */

        // Por ahora retornamos true para marcar internamente como reembolsado
        return true;
    }

    /**
     * Obtener configuración pública para el frontend
     */
    public function getPublicConfig(): array
    {
        return [
            'public_key' => $this->publicKey,
            'test_mode' => $this->test,
            'currency' => $this->currency,
        ];
    }
}
