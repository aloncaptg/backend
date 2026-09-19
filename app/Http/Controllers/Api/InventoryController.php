<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockOpname;
use App\Models\FoodReport;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
    public function index()
    {
        return response()->json(Item::all());
    }

    public function storeItem(Request $request)
    {
        $request->validate([
            'sku' => 'required|string|unique:items,sku',
            'name' => 'required|string',
            'unit' => 'required|string',
            'category' => 'nullable|string',
            'stock_system' => 'nullable|numeric',
            'system_qty' => 'nullable|numeric',
            'min_stock' => 'nullable|numeric',
            'price' => 'nullable|numeric',
        ]);

        $data = $request->only(['sku', 'name', 'unit', 'category', 'min_stock', 'price']);
        $data['category'] = $data['category'] ?? 'Umum';
        $data['stock_system'] = $request->input('stock_system', $request->input('system_qty', 0));
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['price'] = $data['price'] ?? 0;

        $item = Item::create($data);
        return response()->json($item, 201);
    }

    public function updateItem(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $request->validate([
            'sku' => 'string|unique:items,sku,'.$item->id,
            'name' => 'string',
            'unit' => 'string',
            'category' => 'nullable|string',
            'stock_system' => 'nullable|numeric',
            'system_qty' => 'nullable|numeric',
            'min_stock' => 'nullable|numeric',
            'price' => 'nullable|numeric',
        ]);

        $data = $request->only(['sku', 'name', 'unit', 'category', 'min_stock', 'price']);
        if ($request->has('stock_system') || $request->has('system_qty')) {
            $data['stock_system'] = $request->input('stock_system', $request->input('system_qty'));
        }

        $item->update($data);
        return response()->json($item);
    }

    public function destroyItem($id)
    {
        Item::findOrFail($id)->delete();
        return response()->json(['message' => 'Item deleted']);
    }
    public function getItems()
    {
        return response()->json(Item::all());
    }

    public function storeOpname(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'physical_qty' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $item = Item::findOrFail($request->item_id);
        $difference = $request->physical_qty - $item->stock_system;

        $opname = StockOpname::create([
            'user_id' => $request->user()->id,
            'item_id' => $request->item_id,
            'system_qty' => $item->stock_system,
            'physical_qty' => $request->physical_qty,
            'difference' => $difference,
            'notes' => $request->notes,
        ]);
        
        $item->update(['stock_system' => $request->physical_qty]);

        return response()->json(['message' => 'Stock opname recorded', 'data' => $opname], 201);
    }

    public function storeReport(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'qty' => 'required|numeric|min:0.01',
            'report_type' => 'required|in:waste,damaged,expired,in',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $item = Item::findOrFail($request->item_id);
            $path = null;

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('public/food_reports');
                $path = Storage::url($path);
            }

            $report = FoodReport::create([
                'user_id' => $request->user()->id,
                'item_id' => $request->item_id,
                'qty' => $request->qty,
                'report_type' => $request->report_type,
                'photo_path' => $path,
                'notes' => $request->notes,
            ]);

            if (in_array($request->report_type, ['waste', 'damaged', 'expired'])) {
                $item->decrement('stock_system', $request->qty);
            } else if ($request->report_type === 'in') {
                $item->increment('stock_system', $request->qty);
            }

            DB::commit();
            return response()->json(['message' => 'Report recorded successfully', 'data' => $report], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record report', 'error' => $e->getMessage()], 500);
        }
    }
}
