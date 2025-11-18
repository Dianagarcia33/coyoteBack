# Sistema de Pagos ePayco - Documentación

## Resumen
Sistema completo de integración con ePayco para procesar pagos de órdenes en la app Coyote Fitness.

## Flujo de Pago

### 1. Usuario crea una orden
```
POST /api/orders
```
- Crea la orden en estado `pending`
- Reduce stock de productos
- Calcula puntos a ganar (pero NO los otorga aún)

### 2. Usuario inicia el pago
```
GET /api/payments/config
```
Obtiene configuración pública:
```json
{
  "public_key": "pub_test_xxx",
  "test_mode": true,
  "currency": "MXN"
}
```

```
POST /api/payments
{
  "order_id": 1,
  "amount": 250.00,
  "description": "Pago orden #1"
}
```
Respuesta:
```json
{
  "payment": {...},
  "checkout": {
    "name": "Pago orden #1",
    "amount": "250.00",
    "currency": "MXN",
    "public_key": "pub_test_xxx",
    "invoice": "ORD-1-1700123456",
    "extra1": "1",
    "confirmation": "https://tu-dominio.com/api/webhooks/epayco",
    ...
  }
}
```

### 3. Usuario paga con ePayco
La app Android/iOS usa el SDK de ePayco con los datos del `checkout`.

### 4. ePayco notifica vía webhook
```
POST /api/webhooks/epayco
```
El webhook:
- ✅ Verifica la firma MD5
- ✅ Busca el pago
- ✅ Previene duplicados (idempotencia)
- ✅ Actualiza estado del pago
- ✅ Si es `approved`:
  - Marca orden como `completed`
  - **Otorga puntos** al usuario (solo si no se han otorgado antes)
  - Marca `points_awarded = true`

## Endpoints

### Públicos (sin autenticación)
```
POST /api/webhooks/epayco - Webhook de confirmación ePayco
```

### Autenticados
```
GET  /api/payments/config - Configuración pública
POST /api/payments - Crear pago
GET  /api/payments/response - Página de retorno (opcional)
```

### Admin/Gimnasio
```
GET    /api/admin/payments - Listar pagos (con filtros)
GET    /api/admin/payments/{id} - Ver detalles
PATCH  /api/admin/payments/{id}/status - Cambiar estado
POST   /api/admin/payments/{id}/refund - Reembolsar
```

## Filtros en GET /api/admin/payments
```
?status=approved
?user_id=5
?from_date=2025-01-01
?to_date=2025-12-31
```

## Estados de Pago
- `pending` - Pago iniciado, esperando confirmación
- `approved` - Pago aprobado, puntos otorgados
- `rejected` - Pago rechazado
- `cancelled` - Pago cancelado
- `refunded` - Pago reembolsado (puntos revertidos)

## Seguridad

### Verificación de firma
```php
md5(p_cust_id_cliente^p_key^x_ref_payco^x_transaction_id^x_amount^x_currency_code)
```

### Idempotencia
- Campo único: `epayco_ref`
- Si un pago ya está `approved`, ignora webhooks duplicados

### Permisos
- Solo `admin` y `gimnasio` pueden acceder a rutas `/api/admin/payments/*`

## Configuración (.env)
```env
EPAYCO_PUBLIC_KEY=pub_test_xxx
EPAYCO_PRIVATE_KEY=prv_test_xxx
EPAYCO_P_CUST_ID_CLIENTE=xxxxx
EPAYCO_P_KEY=xxxxx
EPAYCO_TEST=true
EPAYCO_CURRENCY=MXN
EPAYCO_BASE_URL=https://secure.epayco.co
```

## Reembolsos
Al reembolsar un pago:
1. Cambia estado del pago a `refunded`
2. Cambia estado de la orden a `cancelled`
3. **Revierte los puntos** (resta puntos del usuario)
4. Marca `points_awarded = false`

## Pruebas con ngrok
```bash
ngrok http 8000
```
Configurar webhook en ePayco:
```
https://xxxx.ngrok.io/api/webhooks/epayco
```

## Logs
Todos los webhooks y errores se registran en `storage/logs/laravel.log`:
```php
Log::info('ePayco webhook recibido', $data);
Log::error('Error procesando webhook', $error);
```

## Base de Datos

### Tabla payments
```sql
id, user_id, order_id, amount, currency, status, 
payment_method, transaction_id, epayco_ref, 
description, meta (JSON), created_at, updated_at
```

### Tabla orders (nuevo campo)
```sql
points_awarded BOOLEAN DEFAULT false
```

## Modelos

### Payment
```php
$payment->user()
$payment->order()
$payment->isApproved()
$payment->canBeRefunded()
```

### Order
```php
$order->payment()
$order->points_awarded // boolean
```

### User
```php
$user->payments()
```
