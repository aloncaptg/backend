<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waste extends Model
{
    protected $fillable = ['item_id', 'reported_by', 'qty', 'reason', 'proof_photo_path'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
