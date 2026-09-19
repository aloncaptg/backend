<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CookingPlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooking_plan_id', 'recipe_id', 'portions'
    ];

    public function plan()
    {
        return $this->belongsTo(CookingPlan::class, 'cooking_plan_id');
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
