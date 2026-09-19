<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Waste;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\Leave;
use App\Models\User;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

class V4Controller extends Controller
{
    // Wastes
    public function getWastes() { return response()->json(Waste::with(['item:id,name,unit', 'reporter:id,name'])->latest()->get()); }

    public function storeWaste(Request $request) {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'qty' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string',
            'proof_photo' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $data = [
                'item_id' => $request->item_id,
                'reported_by' => $request->user()->id,
                'qty' => $request->qty,
                'reason' => $request->reason,
            ];
            if ($request->hasFile('proof_photo')) {
                $data['proof_photo_path'] = $request->file('proof_photo')->store('wastes', 'public');
            }
            $waste = Waste::create($data);

            // Waste reduces available stock
            Item::where('id', $request->item_id)->decrement('stock_system', $request->qty);

            DB::commit();
            return response()->json($waste, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record waste', 'error' => $e->getMessage()], 500);
        }
    }

    // Suppliers
    public function getSuppliers() { return response()->json(Supplier::all()); }
    public function storeSupplier(Request $request) {
        $request->validate(['name' => 'required|string']);
        $supplier = Supplier::create($request->only(['name', 'contact_person', 'phone', 'address']));
        return response()->json($supplier, 201);
    }

    // Tasks (To-Do List & Gamification)
    public function getTasks() { return response()->json(Task::with('assignedTo:id,name')->orderBy('due_date', 'asc')->get()); }

    public function storeTask(Request $request) {
        $request->validate([
            'title' => 'required|string',
            'due_date' => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
            'points' => 'nullable|integer',
        ]);
        $task = Task::create([
            'title' => $request->title,
            'due_date' => $request->due_date,
            'assigned_to' => $request->assigned_to,
            'points' => $request->input('points', 10),
            'is_completed' => false,
        ]);
        return response()->json($task, 201);
    }

    public function updateTask(Request $request, $id) {
        $task = Task::findOrFail($id);
        $isCompleted = $request->boolean('is_completed', $request->boolean('done', false));
        $task->update(['is_completed' => $isCompleted]);

        // Award / revoke performance points to the assignee
        if ($task->assigned_to) {
            $user = User::find($task->assigned_to);
            if ($user) {
                $user->increment('performance_points', $isCompleted ? $task->points : -$task->points);
            }
        }
        return response()->json($task);
    }

    public function completeTask($id) {
        $task = Task::findOrFail($id);
        $task->update(['is_completed' => true]);
        if ($task->assigned_to) {
            User::where('id', $task->assigned_to)->increment('performance_points', $task->points);
        }
        return response()->json($task);
    }

    // Leaves
    public function getLeaves() { return response()->json(Leave::with('user:id,name,role')->latest()->get()); }

    public function storeLeave(Request $request) {
        $request->validate([
            'type' => 'required|in:sick,vacation,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);
        $leave = Leave::create([
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);
        return response()->json($leave, 201);
    }

    public function updateLeaveStatus(Request $request, $id) {
        $leave = Leave::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,approved,rejected']);
        $leave->update(['status' => $request->status]);
        return response()->json($leave);
    }

    // Users (Admin User Management)
    public function getUsers() { return response()->json(User::all()); }
    public function suspendUser($id) {
        $user = User::findOrFail($id);
        $user->update(['status' => !$user->status]);
        return response()->json($user);
    }
}
