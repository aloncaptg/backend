<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Attendance;
use App\Models\StockOpname;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;

class ItemsImport implements ToModel, WithHeadingRow
{
    public function model(array $row): Model|array|null
    {
        return Item::updateOrCreate(
            ['sku' => $row['sku']],
            [
                'name' => $row['name'],
                'category' => $row['category'],
                'unit' => $row['unit'],
                'stock_system' => $row['stock_system'] ?? 0,
                'min_stock' => $row['min_stock'] ?? 0,
            ]
        );
    }
}

class AttendancesExport implements FromCollection, WithHeadings
{
    public function collection(): Enumerable
    {
        return Attendance::with('user')->get()->map(function($att) {
            return [
                'ID' => $att->id,
                'Name' => $att->user->name,
                'Time' => $att->check_in_at,
                'Lat' => $att->latitude,
                'Lng' => $att->longitude,
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'User Name', 'Check In Time', 'Latitude', 'Longitude'];
    }
}

class OpnamesExport implements FromCollection, WithHeadings
{
    public function collection(): Enumerable
    {
        return StockOpname::with(['user', 'item'])->get()->map(function($op) {
            return [
                'ID' => $op->id,
                'User' => $op->user->name,
                'Item' => $op->item->name,
                'System Qty' => $op->system_qty,
                'Physical Qty' => $op->physical_qty,
                'Difference' => $op->difference,
                'Notes' => $op->notes,
                'Date' => $op->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'User', 'Item', 'System Qty', 'Physical Qty', 'Difference', 'Notes', 'Date'];
    }
}

class MonthlyProfitExport implements FromCollection, WithHeadings
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection(): Enumerable
    {
        return Order::whereMonth('delivery_date', $this->month)
            ->whereYear('delivery_date', $this->year)
            ->get()
            ->map(function($order) {
                $laba = ($order->total_revenue ?? 0) - ($order->total_cogs ?? 0);
                return [
                    'ID' => $order->id,
                    'Order Title' => $order->title,
                    'Customer' => $order->customer_name,
                    'Date' => $order->delivery_date,
                    'Status' => $order->status,
                    'Revenue' => $order->total_revenue,
                    'COGS' => $order->total_cogs,
                    'Profit' => $laba,
                ];
            });
    }

    public function headings(): array
    {
        return ['ID', 'Order Title', 'Customer', 'Date', 'Status', 'Revenue', 'COGS', 'Profit'];
    }
}

class EnhancedAttendancesExport implements FromCollection, WithHeadings
{
    public function collection(): Enumerable
    {
        return Attendance::with('user')->get()->map(function($att) {
            $photoUrl = $att->photo_path ? url('storage/' . $att->photo_path) : 'No Photo';
            return [
                'ID' => $att->id,
                'Employee' => $att->user->name,
                'Role' => $att->user->role,
                'Check In' => $att->check_in_at?->format('Y-m-d H:i:s'),
                'Check Out' => $att->check_out_at?->format('Y-m-d H:i:s'),
                'Duration (hours)' => $att->check_in_at && $att->check_out_at 
                    ? round($att->check_in_at->diffInMinutes($att->check_out_at) / 60, 2) 
                    : 'Still Working',
                'Latitude' => $att->latitude,
                'Longitude' => $att->longitude,
                'Photo URL' => $photoUrl,
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'Employee', 'Role', 'Check In', 'Check Out', 'Duration (hours)', 'Latitude', 'Longitude', 'Photo URL'];
    }
}

class OrdersExport implements FromCollection, WithHeadings
{
    public function collection(): Enumerable
    {
        return Order::with('items')->get()->map(function($order) {
            $laba = ($order->total_revenue ?? 0) - ($order->total_cogs ?? 0);
            return [
                'ID' => $order->id,
                'Order Title' => $order->title,
                'Customer' => $order->customer_name,
                'Phone' => $order->customer_phone,
                'Address' => $order->customer_address,
                'Delivery Date' => $order->delivery_date,
                'Status' => $order->status,
                'Revenue' => $order->total_revenue,
                'COGS' => $order->total_cogs,
                'Profit' => $laba,
                'Created' => $order->created_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'Order Title', 'Customer', 'Phone', 'Address', 'Delivery Date', 'Status', 'Revenue', 'COGS', 'Profit', 'Created'];
    }
}

class ExcelController extends Controller
{
    public function importItems(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new ItemsImport, $request->file('file'));

        return response()->json(['message' => 'Items imported successfully']);
    }

    public function exportAttendances()
    {
        return Excel::download(new AttendancesExport, 'attendances.xlsx');
    }

    public function exportOpnames()
    {
        return Excel::download(new OpnamesExport, 'stock_opnames.xlsx');
    }

    public function exportMonthlyReport(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $filename = "monthly_report_{$year}_{$month}.xlsx";
        return Excel::download(new MonthlyProfitExport($month, $year), $filename);
    }

    public function exportOrders()
    {
        return Excel::download(new OrdersExport, 'orders_report.xlsx');
    }

    public function exportEnhancedAttendances()
    {
        return Excel::download(new EnhancedAttendancesExport, 'attendances_detailed.xlsx');
    }

    public function getAttendances()
    {
        $attendances = Attendance::with('user')->orderBy('check_in_at', 'desc')->get();
        return response()->json($attendances);
    }
}
