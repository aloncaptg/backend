<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Delivery;

class OrderController extends Controller
{
    public function index()
    {
        return response()->json(Order::with(['items.recipe', 'delivery.driver'])->orderBy('delivery_date', 'asc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'delivery_date' => 'required|date',
            'total_revenue' => 'required|numeric',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'items' => 'required|array', // [['recipe_id' => 1, 'portions' => 50]]
        ]);

        $order = Order::create([
            'title' => $request->title,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address,
            'delivery_date' => $request->delivery_date,
            'total_revenue' => $request->total_revenue,
            'status' => 'pending',
        ]);

        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'recipe_id' => $item['recipe_id'],
                'portions' => $item['portions'],
            ]);
        }

        // Auto-create unassigned delivery
        Delivery::create([
            'order_id' => $order->id,
            'destination_address' => $request->customer_address ?? 'TBD',
        ]);

        return response()->json($order->load('items'), 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,cooking,ready,delivered']);
        $order->update(['status' => $request->status]);
        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'title' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'total_revenue' => 'nullable|numeric',
            'total_cogs' => 'nullable|numeric',
            'status' => 'nullable|in:pending,cooking,ready,delivered',
        ]);

        $order->update($request->only([
            'title', 'customer_name', 'customer_phone', 'customer_address',
            'delivery_date', 'total_revenue', 'total_cogs', 'status',
        ]));

        return response()->json($order->load('items.recipe', 'delivery'));
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        OrderItem::where('order_id', $order->id)->delete();
        Delivery::where('order_id', $order->id)->delete();
        $order->delete();

        return response()->json(['message' => 'Pesanan berhasil dihapus']);
    }
}
