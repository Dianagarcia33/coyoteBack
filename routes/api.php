<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PointController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\Api\SessionPackageController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ClientGoalController;
use App\Http\Controllers\Api\ClientMeasurementController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\AvailableSlotController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AvailableHoursController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas públicas de productos (para clientes sin login)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Webhook de ePayco (público pero con verificación de firma)
Route::post('/webhooks/epayco', [PaymentWebhookController::class, 'handle']);

// Rutas protegidas con autenticación Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Rutas de puntos para todos los usuarios autenticados
    Route::get('/points', [PointController::class, 'balance']);
    Route::get('/points/history', [PointController::class, 'history']);
    Route::get('/points/rules', [PointController::class, 'rules']);
    
    // Rutas de órdenes para usuarios autenticados
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    
    // Rutas de pagos para usuarios autenticados
    Route::get('/payments/config', [PaymentController::class, 'config']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/response', [PaymentController::class, 'response']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    
    // Ruta temporal para simular aprobación de pago (solo para pruebas)
    Route::post('/payments/{id}/simulate-approval', [PaymentController::class, 'simulateApproval']);
    
    // Rutas de billetera para usuarios autenticados
    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    
    // Rutas de cuentas bancarias
    Route::get('/bank-accounts', [BankAccountController::class, 'index']);
    Route::post('/bank-accounts', [BankAccountController::class, 'store']);
    Route::put('/bank-accounts/{id}', [BankAccountController::class, 'update']);
    Route::delete('/bank-accounts/{id}', [BankAccountController::class, 'destroy']);
    Route::post('/bank-accounts/{id}/set-primary', [BankAccountController::class, 'setPrimary']);
    
    // Rutas de retiros
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);
    Route::get('/withdrawals/{id}', [WithdrawalController::class, 'show']);
    Route::post('/withdrawals/{id}/cancel', [WithdrawalController::class, 'cancel']);
    
    // Rutas de sesiones
    Route::get('/my-packages', [SessionPackageController::class, 'myPackages']);
    Route::get('/my-sessions', [SessionPackageController::class, 'clientSessions']);
    Route::get('/packages/{id}', [SessionPackageController::class, 'show']);
    
    // Rutas de perfil
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile/gym', [ProfileController::class, 'updateGymProfile']);
    Route::put('/profile/trainer', [ProfileController::class, 'updateTrainerProfile']);
    Route::put('/profile/nutritionist', [ProfileController::class, 'updateNutritionistProfile']);
    Route::put('/profile/client', [ProfileController::class, 'updateClientProfile']);
    
    // Rutas de metas (solo clientes)
    Route::get('/goals', [ClientGoalController::class, 'index']);
    Route::post('/goals', [ClientGoalController::class, 'store']);
    Route::put('/goals/{id}', [ClientGoalController::class, 'update']);
    Route::post('/goals/{id}/complete', [ClientGoalController::class, 'complete']);
    Route::delete('/goals/{id}', [ClientGoalController::class, 'destroy']);
    
    // Rutas de medidas (solo clientes)
    Route::get('/measurements', [ClientMeasurementController::class, 'index']);
    Route::post('/measurements', [ClientMeasurementController::class, 'store']);
    Route::get('/measurements/progress', [ClientMeasurementController::class, 'progress']);
    Route::delete('/measurements/{id}', [ClientMeasurementController::class, 'destroy']);
    
    // Rutas de reviews
    Route::post('/professionals/{professionalId}/reviews', [ReviewController::class, 'store']);
    Route::get('/professionals/{professionalId}/reviews', [ReviewController::class, 'index']);
    Route::delete('/professionals/{professionalId}/reviews', [ReviewController::class, 'destroy']);
    
    // Listar usuarios (sin admins) - Requiere autenticación
    Route::get('/users', [AuthController::class, 'getPublicUsers']);
    
    // Rutas de horarios disponibles (para que cliente vea horas libres)
    Route::get('/professionals/{professionalId}/available-hours', [AvailableHoursController::class, 'getAvailableHours']);
    
    // Rutas de slots disponibles (profesionales)
    Route::get('/professionals/{professionalId}/slots', [AvailableSlotController::class, 'index']);
    Route::get('/available-slots/{id}', [AvailableSlotController::class, 'show']);
    
    // Rutas de reservas (clientes)
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    
    // Obtener todos los usuarios incluyendo admins (solo admin)
    Route::get('/admin/all-users', [AuthController::class, 'getAllUsers'])->middleware('role:admin');
    
    // Activar/Desactivar usuario (solo admin)
    Route::patch('/users/{id}/toggle-status', [AuthController::class, 'toggleUserStatus'])->middleware('role:admin');

    // Rutas solo para Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Dashboard de administrador']);
        });
        
        // Gestión de Categorías (Admin)
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        
        // Gestión de Productos (Admin)
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::patch('/products/{id}/stock', [ProductController::class, 'updateStock']);
        Route::get('/products/{id}/history', [ProductController::class, 'history']);
        
        // Gestión de Imágenes de Productos (Admin)
        Route::post('/products/{productId}/images/upload', [ProductImageController::class, 'upload']);
        Route::delete('/products/{productId}/images/delete', [ProductImageController::class, 'delete']);
        Route::put('/products/{productId}/images/replace', [ProductImageController::class, 'replace']);
        
        // Gestión de Puntos (Admin)
        Route::post('/users/{userId}/points/add', [PointController::class, 'addPoints']);
        Route::post('/users/{userId}/points/subtract', [PointController::class, 'subtractPoints']);
        
        // Gestión de Órdenes (Admin)
        Route::get('/orders', [OrderController::class, 'getAllOrders']);
        Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
        
        // Gestión de Pagos (Admin)
        Route::get('/payments', [AdminPaymentController::class, 'index']);
        Route::get('/payments/{id}', [AdminPaymentController::class, 'show']);
        Route::patch('/payments/{id}/status', [AdminPaymentController::class, 'updateStatus']);
        Route::post('/payments/{id}/refund', [AdminPaymentController::class, 'refund']);
        
        // Gestión de Retiros (Solo Admin)
        Route::get('/withdrawals', [WithdrawalController::class, 'adminIndex']);
        Route::post('/withdrawals/{id}/approve', [WithdrawalController::class, 'approve']);
        Route::post('/withdrawals/{id}/complete', [WithdrawalController::class, 'complete']);
        Route::post('/withdrawals/{id}/reject', [WithdrawalController::class, 'reject']);
    });

    // Rutas para Gimnasio
    Route::middleware('role:gimnasio,admin')->prefix('gimnasio')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Dashboard de gimnasio']);
        });
        // Aquí irán las rutas de gestión de instalaciones y membresías
    });

    // Rutas para Entrenadores
    Route::middleware('role:entrenador,admin')->prefix('entrenador')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Dashboard de entrenador']);
        });
        
        // Gestión de paquetes y sesiones
        Route::get('/packages', [SessionPackageController::class, 'professionalPackages']);
        Route::get('/sessions', [SessionPackageController::class, 'professionalSessions']);
        Route::post('/sessions/{sessionId}/complete', [SessionPackageController::class, 'completeSession']);
        
        // Gestión de slots
        Route::get('/my-slots', [AvailableSlotController::class, 'mySlots']);
        Route::post('/slots', [AvailableSlotController::class, 'store']);
        Route::put('/slots/{id}', [AvailableSlotController::class, 'update']);
        Route::delete('/slots/{id}', [AvailableSlotController::class, 'destroy']);
        
        // Gestión de reservas
        Route::get('/bookings', [BookingController::class, 'professionalBookings']);
        Route::get('/bookings/pending', [BookingController::class, 'pendingConfirmations']);
        Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
        Route::post('/bookings/{id}/reject', [BookingController::class, 'reject']);
        Route::post('/bookings/{id}/complete', [BookingController::class, 'complete']);
    });

    // Rutas para Nutricionistas
    Route::middleware('role:nutricionista,admin')->prefix('nutricionista')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Dashboard de nutricionista']);
        });
        
        // Gestión de paquetes y sesiones
        Route::get('/packages', [SessionPackageController::class, 'professionalPackages']);
        Route::get('/sessions', [SessionPackageController::class, 'professionalSessions']);
        Route::post('/sessions/{sessionId}/complete', [SessionPackageController::class, 'completeSession']);
        
        // Gestión de slots
        Route::get('/my-slots', [AvailableSlotController::class, 'mySlots']);
        Route::post('/slots', [AvailableSlotController::class, 'store']);
        Route::put('/slots/{id}', [AvailableSlotController::class, 'update']);
        Route::delete('/slots/{id}', [AvailableSlotController::class, 'destroy']);
        
        // Gestión de reservas
        Route::get('/bookings', [BookingController::class, 'professionalBookings']);
        Route::get('/bookings/pending', [BookingController::class, 'pendingConfirmations']);
        Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
        Route::post('/bookings/{id}/reject', [BookingController::class, 'reject']);
        Route::post('/bookings/{id}/complete', [BookingController::class, 'complete']);
    });

    // Rutas para Clientes
    Route::middleware('role:cliente,admin')->prefix('cliente')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Dashboard de cliente']);
        });
        // Aquí irán las rutas de acceso a entrenamientos y planes
    });
});
