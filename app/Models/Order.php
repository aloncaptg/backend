<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'title', 'customer_name', 'customer_phone', 'customer_address',
        'delivery_date', 'total_cogs', 'total_revenue', 'status',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }
}
