<?php

namespace App\Services;

use App\DTO\Admin\SearchCategoryDTO;
use App\DTO\Admin\StoreUpdateCategoryDTO;
use App\Exceptions\Admin\CategoryException;
use App\Models\Category;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CategoryServices
{
    public function getAll(): CursorPaginator
    {
        $user = Auth::user();
        $categories = Category::where('user_id', $user->id)->cursorPaginate(12);

        return $categories;
    }

    public function search(SearchCategoryDTO $data): Collection
    {
        $user = Auth::user();
        $categories = Category::where('user_id', $user->id)
            ->when($data->name != 'all', function ($query) use ($data) {
                $query->where('name', 'like', '%' . $data->name . '%');
            })
            ->where('type', $data->type)
            ->get();
        
        return $categories;
    }

    public function show(int $id): Category
    {
        $user = Auth::user();
        $category = Category::where('user_id', $user->id)->where('id', $id)->first();

        if (!$category || $category?->user_id != Auth::user()->id) {
            throw new CategoryException('Categoria não encontrada');
        }

        return $category;
    }

    public function store(StoreUpdateCategoryDTO $data): Category
    {
        $user = Auth::user();
        $category = Category::where('user_id', $user->id)
            ->where('name', $data->name)
            ->where('type', $data->type)
            ->first();

        if ($category) {
            throw new CategoryException('Categoria já existente, verifique os dados');
        }


        $category = $user->categories()->create([
            'name' => $data->name,
            'type' => $data->type,
        ]);

        if (!$category) {
            throw new CategoryException('Erro ao criar categoria');
        }

        return $category;
    }

    public function update(int $id, StoreUpdateCategoryDTO $data): Category
    {
        $category = Category::where('id', $id)
            ->where('user_id', Auth::user()->id)->first();

        if (!$category || $category?->user_id != Auth::user()->id) {
            throw new CategoryException('Categoria não encontrada');
        }

        if ($category->name == $data->name && $category->type == $data->type) {
            throw new CategoryException('Categoria já existente, verifique os dados');
        }

        $category->update([
            'name' => $data->name,
            'type' => $data->type,
        ]);

        return $category;
    }

    public function delete(int $id): void
    {
        $user = Auth::user();
        $category = Category::where('user_id', $user->id)->where('id', $id)->first();
        if (!$category) {
            throw new CategoryException('Categoria não encontrada');
        }

        if ($category->invoices()->exists()) {
            throw new CategoryException('Categoria não pode ser deletada, pois possui movimentações');
        }

        $category->delete();
    }
}