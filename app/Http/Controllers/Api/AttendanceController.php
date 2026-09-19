<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'qr_payload' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'type' => 'nullable|in:clock_in,clock_out',
        ]);

        $type = $request->input('type', 'clock_in');
        $userId = $request->user()->id;
        $path = $request->file('photo')->store('attendances', 'public');

        if ($type === 'clock_out') {
            // Close today's open attendance record if any
            $today = Attendance::where('user_id', $userId)
                ->whereDate('check_in_at', now()->toDateString())
                ->whereNull('check_out_at')
                ->latest('check_in_at')
                ->first();

            if ($today) {
                $today->update([
                    'check_out_at' => now(),
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ]);

                return response()->json(['message' => 'Clock-out recorded successfully', 'data' => $today], 200);
            }
        }

        $attendance = Attendance::create([
            'user_id' => $userId,
            'check_in_at' => now(),
            'photo_path' => Storage::url($path),
            'qr_payload' => $request->qr_payload ?? 'MOBILE_GPS selfie verification',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json(['message' => 'Attendance recorded successfully', 'data' => $attendance], 201);
    }
    
    public function index()
    {
        return response()->json(Attendance::with('user:id,name,role')->latest('check_in_at')->get());
    }
}
