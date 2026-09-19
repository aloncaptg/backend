<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Delivery;

class DeliveryController extends Controller
{
    public function index()
    {
        $deliveries = Delivery::with(['order', 'driver:id,name,role,phone_number'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($deliveries);
    }

    public function assign(Request $request, $id)
    {
        $delivery = Delivery::findOrFail($id);
        $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $delivery->update([
            'driver_id' => $request->driver_id,
            'status' => $delivery->status === 'delivered' ? 'delivered' : 'assigned',
        ]);

        return response()->json($delivery->load('driver:id,name,role'));
    }

    public function myDeliveries(Request $request)
    {
        $deliveries = Delivery::where('driver_id', $request->user()->id)->with('order')->get();
        return response()->json($deliveries);
    }

    public function updateDelivery(Request $request, $id)
    {
        $delivery = Delivery::findOrFail($id);
        
        $data = ['status' => $request->status];
        if ($request->hasFile('proof_photo')) {
            $data['proof_photo_path'] = $request->file('proof_photo')->store('proofs', 'public');
        }
        if ($request->has('latitude') && $request->has('longitude')) {
            $data['latitude'] = $request->latitude;
            $data['longitude'] = $request->longitude;
        }

        $delivery->update($data);

        if ($delivery->status == 'delivered') {
            $delivery->order->update(['status' => 'delivered']);
        }

        return response()->json($delivery);
    }
}
