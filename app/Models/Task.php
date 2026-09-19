<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['assigned_to', 'title', 'is_completed', 'points', 'due_date'];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'date',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
