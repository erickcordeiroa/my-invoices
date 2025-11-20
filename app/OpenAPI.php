<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'My Invoices API',
    description: 'API de controle financeiro com fluxo de caixa e separação por carteira. Permite lançar contas a pagar e a receber de forma única, parcelada ou mensal.',
    contact: new OA\Contact(
        name: 'My Invoices API Support'
    )
)]
#[OA\Server(
    url: '/api/v1',
    description: 'API Base URL'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    name: 'Authorization',
    in: 'header',
    description: 'Enter token in format (Bearer <token>)'
)]
#[OA\Tag(
    name: 'Autenticação',
    description: 'Endpoints para autenticação, registro e recuperação de senha'
)]
#[OA\Tag(
    name: 'Categorias',
    description: 'Endpoints para gerenciamento de categorias de receitas e despesas'
)]
class OpenAPI
{
}

