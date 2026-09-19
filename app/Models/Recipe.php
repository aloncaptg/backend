<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'serving_size', 'category', 'photo_path'
    ];

    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
