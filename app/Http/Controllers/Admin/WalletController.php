<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\SearchWalletDTO;
use App\DTO\Admin\StoreUpdateWalletDTO;
use App\Exceptions\Admin\WalletException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchWalletRequest;
use App\Http\Requests\Admin\StoreUpdateWalletRequest;
use App\Http\Resources\Admin\WalletResource;
use App\Services\WalletServices;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'Carteiras', description: 'Endpoints para gerenciamento de carteiras')]
class WalletController extends Controller
{
    public function __construct(
        private readonly WalletServices $walletServices
    ){}

    #[OA\Get(
        path: '/wallets',
        summary: 'Listar carteiras',
        description: 'Retorna todas as carteiras do usuário autenticado',
        tags: ['Carteiras'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de carteiras',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Carteira Principal'),
                            new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.50),
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
            $wallets = $this->walletServices->getAll();
            return response()->json(
                WalletResource::collection($wallets), 
                Response::HTTP_OK
            );
        } catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/wallets/search',
        summary: 'Buscar carteiras',
        description: 'Busca carteiras por nome',
        tags: ['Carteiras'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'query',
                required: true,
                description: 'Nome da carteira (busca parcial)',
                schema: new OA\Schema(type: 'string', example: 'Principal')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de carteiras encontradas',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Carteira Principal'),
                            new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.50),
                        ]
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Dados de validação inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function search(SearchWalletRequest $request)
    {
        try {
            $search = new SearchWalletDTO($request->name);
            $wallets = $this->walletServices->search($search);
            return response()->json(
                WalletResource::collection($wallets), 
                Response::HTTP_OK
            );
        } catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/wallets/{id}',
        summary: 'Obter carteira',
        description: 'Retorna os detalhes de uma carteira específica',
        tags: ['Carteiras'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da carteira',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalhes da carteira',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Carteira Principal'),
                        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.50),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Carteira não encontrada'),
        ]
    )]
    public function show(int $id)
    {
        try {
            $wallet = $this->walletServices->show($id);
            return response()->json(
                new WalletResource($wallet), 
                Response::HTTP_OK
            );
        } catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/wallets',
        summary: 'Criar carteira',
        description: 'Cria uma nova carteira para o usuário autenticado',
        tags: ['Carteiras'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Carteira Principal'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Carteira criada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Carteira Principal'),
                        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 0.00),
                    ],
                    example: [
                        'id' => 1,
                        'name' => 'Carteira Principal',
                        'balance' => 0.00
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro ao criar carteira (ex: carteira duplicada)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Já existe uma carteira com este nome'),
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
    public function store(StoreUpdateWalletRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new StoreUpdateWalletDTO($validated['name']);
            $wallet = $this->walletServices->store($data);
            return response()->json(
                new WalletResource($wallet), 
                Response::HTTP_CREATED
            );
        } catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        path: '/wallets/{id}',
        summary: 'Atualizar carteira',
        description: 'Atualiza uma carteira existente',
        tags: ['Carteiras'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da carteira',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Carteira Principal'),
                ],
                example: [
                    'name' => 'Carteira Principal'
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Carteira atualizada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Carteira Principal'),
                        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.50),
                    ],
                    example: [
                        'id' => 1,
                        'name' => 'Carteira Principal',
                        'balance' => 1500.50
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro ao atualizar carteira',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Já existe uma carteira com este nome'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(
                response: 404,
                description: 'Carteira não encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Carteira não encontrada'),
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
    public function update(int $id, StoreUpdateWalletRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new StoreUpdateWalletDTO($validated['name']);
            $wallet = $this->walletServices->update($id, $data);
            
            return response()->json(
                new WalletResource($wallet),
                Response::HTTP_OK
            );
        }
        catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/wallets/{id}',
        summary: 'Excluir carteira',
        description: 'Exclui uma carteira. Não é possível excluir carteiras com movimentações vinculadas.',
        tags: ['Carteiras'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da carteira',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Carteira excluída com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Carteira deletada com sucesso'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Erro ao excluir carteira (ex: possui movimentações vinculadas)'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Carteira não encontrada'),
        ]
    )]
    public function destroy(int $id)
    {
        try {
            $this->walletServices->destroy($id);
            return response()->json(['message' => 'Carteira deletada com sucesso'], Response::HTTP_OK);
        } catch (WalletException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
