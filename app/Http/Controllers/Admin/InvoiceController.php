<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\PayInvoiceDTO;
use App\DTO\Admin\SearchInvoiceDTO;
use App\DTO\Admin\StoreUpdateInvoiceDTO;
use App\Exceptions\Admin\InvoiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayInvoiceRequest;
use App\Http\Requests\Admin\SearchInvoiceRequest;
use App\Http\Requests\Admin\StoreUpdateInvoiceRequest;
use App\Http\Resources\Admin\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceServices;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'Invoices', description: 'Endpoints para gerenciamento de faturas (contas a pagar e receber)')]
class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceServices $invoiceServices
    ) {}

    #[OA\Get(
        path: '/invoices',
        summary: 'Listar invoices',
        description: 'Retorna todas as invoices do usuário autenticado',
        tags: ['Invoices'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de invoices',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'description', type: 'string', example: 'Pagamento de fornecedor'),
                            new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                            new OA\Property(property: 'amount', type: 'integer', example: 150000),
                            new OA\Property(property: 'status', type: 'string', enum: ['unpaid', 'paid', 'overdue'], example: 'unpaid'),
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
            $invoices = $this->invoiceServices->getAll();
            return response()->json(
                InvoiceResource::collection($invoices),
                Response::HTTP_OK
            );
        } catch (InvoiceException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/invoices/search',
        summary: 'Buscar invoices',
        description: 'Busca invoices com filtros diversos',
        tags: ['Invoices'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'description',
                in: 'query',
                required: false,
                description: 'Descrição da invoice (busca parcial)',
                schema: new OA\Schema(type: 'string', example: 'Fornecedor')
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                description: 'Tipo da invoice',
                schema: new OA\Schema(type: 'string', enum: ['income', 'expense'], example: 'expense')
            ),
            new OA\Parameter(
                name: 'wallet_id',
                in: 'query',
                required: false,
                description: 'ID da carteira',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'category_id',
                in: 'query',
                required: false,
                description: 'ID da categoria',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Status da invoice',
                schema: new OA\Schema(type: 'string', enum: ['unpaid', 'paid', 'overdue'], example: 'unpaid')
            ),
            new OA\Parameter(
                name: 'date_from',
                in: 'query',
                required: false,
                description: 'Data inicial (Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-01-01')
            ),
            new OA\Parameter(
                name: 'date_to',
                in: 'query',
                required: false,
                description: 'Data final (Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-12-31')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de invoices encontradas',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'description', type: 'string', example: 'Pagamento de fornecedor'),
                            new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                            new OA\Property(property: 'amount', type: 'integer', example: 150000),
                            new OA\Property(property: 'status', type: 'string', enum: ['unpaid', 'paid', 'overdue'], example: 'unpaid'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Dados de validação inválidos ou nenhuma invoice encontrada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function search(SearchInvoiceRequest $request)
    {
        try {
            $search = new SearchInvoiceDTO(
                $request->type,
                $request->wallet_id,
                $request->category_id,
                $request->status,
                $request->date_from,
                $request->date_to
            );
            $invoices = $this->invoiceServices->search($search);
            return response()->json(
                InvoiceResource::collection($invoices),
                Response::HTTP_OK
            );
        } catch (InvoiceException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/invoices/{id}',
        summary: 'Obter invoice',
        description: 'Retorna os detalhes de uma invoice específica',
        tags: ['Invoices'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da invoice',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalhes da invoice',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'description', type: 'string', example: 'Pagamento de fornecedor'),
                        new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                        new OA\Property(property: 'amount', type: 'integer', example: 150000),
                        new OA\Property(property: 'status', type: 'string', enum: ['unpaid', 'paid', 'overdue'], example: 'unpaid'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Invoice não encontrada'),
        ]
    )]
    public function show(Invoice $invoice)
    {
        try {
            $invoice = $this->invoiceServices->show($invoice->id);
            return response()->json(new InvoiceResource($invoice), Response::HTTP_OK);
        } catch (InvoiceException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/invoices',
        summary: 'Criar invoice',
        description: 'Cria uma nova invoice (única, parcelada ou mensal)',
        tags: ['Invoices'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['wallet_id', 'category_id', 'type', 'amount', 'due_at'],
                properties: [
                    new OA\Property(property: 'wallet_id', type: 'integer', example: 1),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'description', type: 'string', example: 'Pagamento de fornecedor'),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    new OA\Property(property: 'amount', type: 'integer', example: 150000),
                    new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
                    new OA\Property(property: 'due_at', type: 'string', format: 'date', example: '2025-01-31'),
                    new OA\Property(property: 'repeat_when', type: 'string', enum: ['monthly'], example: 'monthly'),
                    new OA\Property(property: 'period', type: 'string', enum: ['monthly'], example: 'monthly'),
                    new OA\Property(property: 'enrollments', type: 'integer', example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Invoice criada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'description', type: 'string', example: 'Pagamento de fornecedor'),
                        new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                        new OA\Property(property: 'amount', type: 'integer', example: 150000),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro ao criar invoice',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Carteira não encontrada ou não pertence ao usuário'),
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
    public function store(StoreUpdateInvoiceRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new StoreUpdateInvoiceDTO(
                (int) $validated['wallet_id'],
                (int) $validated['category_id'],
                $validated['description'] ?? null,
                $validated['type'],
                (int) $validated['amount'],
                $validated['currency'] ?? 'BRL',
                $validated['due_at'],
                $validated['repeat_when'] ?? null,
                $validated['period'] ?? null,
                isset($validated['enrollments']) ? (int) $validated['enrollments'] : null
            );
            $invoice = $this->invoiceServices->store($data);
            
            if ($invoice instanceof \Illuminate\Database\Eloquent\Collection) {
                return response()->json(
                    InvoiceResource::collection($invoice),
                    Response::HTTP_CREATED
                );
            }
            
            return response()->json(new InvoiceResource($invoice), Response::HTTP_CREATED);
        } catch (InvoiceException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        path: '/invoices/{id}',
        summary: 'Atualizar invoice',
        description: 'Atualiza uma invoice existente',
        tags: ['Invoices'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da invoice',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['wallet_id', 'category_id', 'type', 'amount', 'due_at'],
                properties: [
                    new OA\Property(property: 'wallet_id', type: 'integer', example: 1),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'description', type: 'string', example: 'Pagamento de fornecedor'),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    new OA\Property(property: 'amount', type: 'integer', example: 150000),
                    new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
                    new OA\Property(property: 'due_at', type: 'string', format: 'date', example: '2025-01-31'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice atualizada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'description', type: 'string', example: 'Pagamento de fornecedor'),
                        new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                        new OA\Property(property: 'amount', type: 'integer', example: 150000),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro ao atualizar invoice',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invoice não encontrada'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(
                response: 404,
                description: 'Invoice não encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invoice não encontrada'),
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
    public function update(Invoice $invoice, StoreUpdateInvoiceRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new StoreUpdateInvoiceDTO(
                (int) $validated['wallet_id'],
                (int) $validated['category_id'],
                $validated['description'] ?? null,
                $validated['type'],
                (int) $validated['amount'],
                $validated['currency'] ?? 'BRL',
                $validated['due_at'],
                $validated['repeat_when'] ?? null,
                $validated['period'] ?? null,
                isset($validated['enrollments']) ? (int) $validated['enrollments'] : null
            );
            $invoice = $this->invoiceServices->update($invoice->id, $data);
            return response()->json(new InvoiceResource($invoice), Response::HTTP_OK);
        } catch (InvoiceException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/invoices/{id}',
        summary: 'Excluir invoice',
        description: 'Exclui uma invoice. Não é possível excluir invoices parceladas ou com parcelas relacionadas.',
        tags: ['Invoices'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da invoice',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice excluída com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invoice deletada com sucesso'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Erro ao excluir invoice'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Invoice não encontrada'),
        ]
    )]
    public function destroy(Invoice $invoice)
    {
        try {
            $this->invoiceServices->delete($invoice->id);
            return response()->json(['message' => 'Invoice deletada com sucesso'], Response::HTTP_OK);
        } catch (InvoiceException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/invoices/{invoice}/pay',
        summary: 'Marcar invoice como paga',
        description: 'Marca uma invoice como paga e atualiza o saldo da carteira',
        tags: ['Invoices'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'invoice',
                in: 'path',
                required: true,
                description: 'ID da invoice',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'paid_at', type: 'string', format: 'date', example: '2025-01-15'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice marcada como paga',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'status', type: 'string', enum: ['paid'], example: 'paid'),
                        new OA\Property(property: 'paid_at', type: 'string', format: 'date', example: '2025-01-15'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Erro ao marcar como paga (ex: já está paga)'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Invoice não encontrada'),
        ]
    )]
    public function pay(Invoice $invoice, PayInvoiceRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new PayInvoiceDTO($validated['paid_at'] ?? null);
            $invoice = $this->invoiceServices->pay($invoice->id, $data);
            return response()->json(new InvoiceResource($invoice), Response::HTTP_OK);
        } catch (InvoiceException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/invoices/{invoice}/unpay',
        summary: 'Marcar invoice como não paga',
        description: 'Marca uma invoice como não paga e reverte o saldo da carteira',
        tags: ['Invoices'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'invoice',
                in: 'path',
                required: true,
                description: 'ID da invoice',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice marcada como não paga',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'status', type: 'string', enum: ['unpaid', 'overdue'], example: 'unpaid'),
                        new OA\Property(property: 'paid_at', type: 'null', example: null),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Erro ao marcar como não paga (ex: não está paga)'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Invoice não encontrada'),
        ]
    )]
    public function unpay(Invoice $invoice)
    {
        try {
            $invoice = $this->invoiceServices->unpay($invoice->id);
            return response()->json(new InvoiceResource($invoice), Response::HTTP_OK);
        } catch (InvoiceException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

