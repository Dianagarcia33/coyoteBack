<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Obtener todas las órdenes del usuario autenticado
     */
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Crear una nueva orden
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $total = 0;
            $orderItems = [];

            // Validar stock y calcular total
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para el producto: {$product->name}");
                }

                $subtotal = $product->price * $item['quantity'];
                $total += $subtotal;

                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            // Calcular puntos a ganar
            $pointsEarned = Order::calculatePoints($total);

            // Crear la orden en estado PENDING (ya no completed)
            $order = $user->orders()->create([
                'total' => $total,
                'points_earned' => $pointsEarned,
                'status' => 'pending', // Cambiado de 'completed' a 'pending'
            ]);

            // Crear items de la orden y reducir stock
            foreach ($orderItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Reducir stock del producto
                $item['product']->decrementStock($item['quantity']);
            }

            // YA NO SE OTORGAN PUNTOS AQUÍ
            // Los puntos se otorgarán cuando el pago sea aprobado (en PaymentWebhookController)

            DB::commit();

            // Cargar relaciones para la respuesta
            $order->load('items.product');

            return response()->json([
                'message' => 'Orden creada exitosamente',
                'order' => $order,
                'points_earned' => $pointsEarned,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la orden',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener una orden específica
     */
    public function show(Request $request, $id)
    {
        $order = $request->user()
            ->orders()
            ->with('items.product')
            ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Obtener todas las órdenes (solo admin)
     */
    public function getAllOrders(Request $request)
    {
        $orders = Order::with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * Actualizar estado de orden (solo admin)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Estado de orden actualizado',
            'order' => $order,
        ]);
    }
}
