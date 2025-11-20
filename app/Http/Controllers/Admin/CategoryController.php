<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\{SearchCategoryDTO, StoreUpdateCategoryDTO};
use App\Exceptions\Admin\CategoryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\{SearchCategoryRequest, StoreUpdateCategoryRequest};
use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use App\Services\CategoryServices;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryServices $categoryServices
    ){}

    public function index()
    {
        try {
            $categories = $this->categoryServices->getAll();
            return response()->json(
                CategoryResource::collection($categories),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function search(SearchCategoryRequest $request)
    {
        try {
            $search = new SearchCategoryDTO($request->name, $request->type);
            $categories = $this->categoryServices->search($search);
            return response()->json(
                CategoryResource::collection($categories), 
                Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(StoreUpdateCategoryRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new StoreUpdateCategoryDTO($validated['name'], $validated['type']);
            $category = $this->categoryServices->store($data);
            return response()->json(new CategoryResource($category), Response::HTTP_CREATED);
        } catch (CategoryException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Category $category)
    {
        try {
            $category = $this->categoryServices->show($category->id);
            return response()->json(new CategoryResource($category), Response::HTTP_OK);
        } catch (CategoryException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Category $category, StoreUpdateCategoryRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new StoreUpdateCategoryDTO($validated['name'], $validated['type']);
            $category = $this->categoryServices->update($category->id, $data);
            return response()->json(new CategoryResource($category), Response::HTTP_OK);
        }
        catch (CategoryException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Category $category)
    {
        try {
            $this->categoryServices->delete($category->id);
            return response()->json(['message' => 'Categoria deletada com sucesso'], Response::HTTP_OK);
        } catch (CategoryException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
