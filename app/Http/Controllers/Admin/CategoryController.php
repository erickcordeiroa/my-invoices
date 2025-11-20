<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\{SearchCategoryDTO, StoreUpdateCategoryDTO};
use App\Exceptions\Admin\CategoryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\{SearchCategoryRequest, StoreUpdateCategoryRequest};
use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use App\Services\CategoryServices;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'Categorias', description: 'Endpoints para gerenciamento de categorias de receitas e despesas')]
class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryServices $categoryServices
    ){}

    #[OA\Get(
        path: '/categories',
        summary: 'Listar categorias',
        description: 'Retorna todas as categorias do usuário autenticado',
        tags: ['Categorias'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de categorias',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
                            new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
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

    #[OA\Get(
        path: '/categories/search',
        summary: 'Buscar categorias',
        description: 'Busca categorias por nome e/ou tipo',
        tags: ['Categorias'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'query',
                required: true,
                description: 'Nome da categoria (busca parcial)',
                schema: new OA\Schema(type: 'string', example: 'Alimentação')
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: true,
                description: 'Tipo da categoria',
                schema: new OA\Schema(type: 'string', enum: ['income', 'expense'], example: 'expense')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de categorias encontradas',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
                            new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Dados de validação inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function search(SearchCategoryRequest $request)
    {
        try {
            $search = new SearchCategoryDTO($request->name, $request->type);
            $categories = $this->categoryServices->search($search);
            return response()->json(
                CategoryResource::collection($categories), 
                Response::HTTP_OK);
        } catch (CategoryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/categories',
        summary: 'Criar categoria',
        description: 'Cria uma nova categoria para o usuário autenticado',
        tags: ['Categorias'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Alimentação'),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Categoria criada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
                        new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    ],
                    example: [
                        'id' => 1,
                        'name' => 'Alimentação',
                        'type' => 'expense'
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro ao criar categoria (ex: categoria duplicada)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Já existe uma categoria com este nome e tipo'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(
                response: 422,
                description: 'Dados de validação inválidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Dados inválidos'),
                    ]
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: '/categories/{id}',
        summary: 'Obter categoria',
        description: 'Retorna os detalhes de uma categoria específica',
        tags: ['Categorias'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da categoria',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalhes da categoria',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
                        new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Categoria não encontrada'),
        ]
    )]
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

    #[OA\Put(
        path: '/categories/{id}',
        summary: 'Atualizar categoria',
        description: 'Atualiza uma categoria existente',
        tags: ['Categorias'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da categoria',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Alimentação'),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                ],
                example: [
                    'name' => 'Alimentação',
                    'type' => 'expense'
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoria atualizada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
                        new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    ],
                    example: [
                        'id' => 1,
                        'name' => 'Alimentação',
                        'type' => 'expense'
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro ao atualizar categoria',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Já existe uma categoria com este nome e tipo'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(
                response: 404,
                description: 'Categoria não encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Categoria não encontrada'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dados de validação inválidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Dados inválidos'),
                    ]
                )
            ),
        ]
    )]
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

    #[OA\Delete(
        path: '/categories/{id}',
        summary: 'Excluir categoria',
        description: 'Exclui uma categoria. Não é possível excluir categorias com movimentações vinculadas.',
        tags: ['Categorias'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da categoria',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoria excluída com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Categoria deletada com sucesso'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Erro ao excluir categoria (ex: possui movimentações vinculadas)'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Categoria não encontrada'),
        ]
    )]
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
