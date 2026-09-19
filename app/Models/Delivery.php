<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = ['order_id', 'driver_id', 'destination_address', 'status', 'proof_photo_path', 'latitude', 'longitude'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
