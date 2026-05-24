<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where(function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)
                  ->orWhere('is_default', true);
        })->latest()->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $category = Category::create([
            'user_id' => $request->user()->id,
            'is_default' => false,
            ...$validated
        ]);

        return response()->json([
            'message' => 'Category created',
            'data' => $category
        ], 201);
    }

    public function show(
        Request $request,
        Category $category
    ) {
        if (
            $category->user_id !== $request->user()->id &&
            !$category->is_default
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json($category);
    }

    public function update(
        Request $request,
        Category $category
    ) {
        if ($category->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:income,expense',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated',
            'data' => $category
        ]);
    }

    public function destroy(
        Request $request,
        Category $category
    ) {
        if ($category->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted'
        ]);
    }
}