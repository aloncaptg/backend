<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'recipe_id', 'portions'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
