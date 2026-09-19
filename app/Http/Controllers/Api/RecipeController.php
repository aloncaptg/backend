<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Item;
use App\Models\RecipeIngredient;

class RecipeController extends Controller
{
    public function getRecipes()
    {
        return response()->json(Recipe::with('ingredients.item:id,name,unit,stock_system')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'portions' => 'nullable|integer',
            'serving_size' => 'nullable|integer',
            'category' => 'nullable|string|max:100',
            'photo' => 'nullable|image|max:5120',
        ]);

        $data = [
            'name' => $request->name,
            'serving_size' => $request->input('serving_size', $request->input('portions', 1)),
            'category' => $request->input('category', 'Makanan'),
        ];
        
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('recipes', 'public');
        }

        $recipe = Recipe::create($data);

        return response()->json($recipe, 201);
    }

    public function addIngredient(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity_per_serving' => 'required|numeric|min:0.0001',
            'unit' => 'nullable|string',
        ]);

        $item = Item::find($request->item_id);

        $ingredient = RecipeIngredient::updateOrCreate(
            ['recipe_id' => $recipe->id, 'item_id' => $request->item_id],
            [
                'quantity_per_serving' => $request->quantity_per_serving,
                'unit' => $request->unit ?? $item->unit,
            ]
        );

        return response()->json($ingredient->load('item:id,name,unit'), 201);
    }

    public function removeIngredient($id, $ingredientId)
    {
        RecipeIngredient::where('recipe_id', $id)->where('id', $ingredientId)->delete();
        return response()->json(['message' => 'Ingredient removed']);
    }

    public function calculateMaterials(Request $request)
    {
        $request->validate([
            'recipes' => 'required|array',
            'recipes.*.recipe_id' => 'required|exists:recipes,id',
            'recipes.*.portions' => 'required|integer|min:1',
        ]);

        $requiredMaterials = [];

        foreach ($request->recipes as $plan) {
            $recipe = Recipe::with('ingredients.item')->find($plan['recipe_id']);
            $portions = $plan['portions'];

            foreach ($recipe->ingredients as $ingredient) {
                $item = $ingredient->item;
                $totalRequired = $ingredient->quantity_per_serving * $portions;

                if (!isset($requiredMaterials[$item->id])) {
                    $requiredMaterials[$item->id] = [
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'category' => $item->category,
                        'unit' => $item->unit,
                        'current_stock' => $item->stock_system,
                        'total_required' => 0,
                    ];
                }

                $requiredMaterials[$item->id]['total_required'] += $totalRequired;
            }
        }

        $output = [];
        foreach ($requiredMaterials as $material) {
            $shortage = max(0, $material['total_required'] - $material['current_stock']);
            $material['shortage_qty'] = $shortage;
            $material['status'] = $shortage > 0 ? 'NEED_TO_BUY' : 'STOCK_ENOUGH';

            $category = $material['category'];
            if (!isset($output[$category])) {
                $output[$category] = [];
            }
            $output[$category][] = $material;
        }

        return response()->json($output);
    }
}
