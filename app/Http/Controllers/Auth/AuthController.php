<?php

namespace App\Http\Controllers\Auth;

use App\DTO\Auth\{ActivateDTO, ForgotDTO, LoginDTO, RegisterDTO, ResetPasswordDTO};
use App\Exceptions\Auth\{ActivateException, ForgotPasswordException, LoginException, RegisterException, ResetPasswordException};
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\{ActivateRequest, ForgotPasswordRequest, LoginRequest, RegisterRequest, ResetPasswordRequest};
use App\Http\Resources\Auth\RegisterResource;
use App\Services\AuthServices;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'Autenticação', description: 'Endpoints para autenticação, registro e recuperação de senha')]
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServices $authServices
    ){}

    #[OA\Post(
        path: '/login',
        summary: 'Realizar login',
        description: 'Autentica um usuário e retorna um token de acesso',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'senha123', minLength: 4),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login realizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456'),
                        new OA\Property(property: 'message', type: 'string', example: 'Login realizado com sucesso'),
                    ],
                    example: [
                        'token' => '1|abcdef1234567890',
                        'message' => 'Login realizado com sucesso'
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciais inválidas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'null', example: null),
                        new OA\Property(property: 'message', type: 'string', example: 'Usuário ou senha inválidos'),
                    ],
                    example: [
                        'token' => null,
                        'message' => 'Usuário ou senha inválidos'
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dados de validação inválidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'null', example: null),
                        new OA\Property(property: 'message', type: 'string', example: 'Usuário ou senha inválidos'),
                    ]
                )
            ),
        ]
    )]
    public function login(LoginRequest $request)
    {
        try {
            $validated = $request->validated();
            $credentials = new LoginDTO($validated['email'], $validated['password']);
            $token = $this->authServices->login($credentials);

            return response()->json([
                'token' => $token,
                'message' => 'Login realizado com sucesso'
            ], Response::HTTP_OK);
        } catch (LoginException $e) {
            return response()->json([
                'token' => null,
                'message' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return response()->json([
                'token' => null,
                'message' => 'Erro ao realizar login'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/register',
        summary: 'Registrar novo usuário',
        description: 'Cria uma nova conta de usuário. Um email de ativação será enviado.',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'João Silva'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'senha123', minLength: 4),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'senha123'),
                ],
                example: [
                    'name' => 'João Silva',
                    'email' => 'usuario@example.com',
                    'password' => 'senha123',
                    'password_confirmation' => 'senha123'
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuário registrado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'João Silva'),
                                new OA\Property(property: 'email', type: 'string', example: 'usuario@example.com'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Usuário cadastrado com sucesso, acesse seu e-mail e clique no link de ativação'),
                    ],
                    example: [
                        'user' => [
                            'name' => 'João Silva',
                            'email' => 'usuario@example.com'
                        ],
                        'message' => 'Usuário cadastrado com sucesso, acesse seu e-mail e clique no link de ativação'
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro ao registrar usuário',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'null', example: null),
                        new OA\Property(property: 'message', type: 'string', example: 'Email já cadastrado'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dados de validação inválidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'null', example: null),
                        new OA\Property(property: 'message', type: 'string', example: 'Dados inválidos'),
                    ]
                )
            ),
        ]
    )]
    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            $register = new RegisterDTO($validated['name'], $validated['email'], $validated['password']);
            $user = $this->authServices->register($register);
            return response()->json([
                'user' => new RegisterResource($user),
                'message' => 'Usuário cadastrado com sucesso, acesse seu e-mail e clique no link de ativação'
            ], Response::HTTP_CREATED);
        } catch (RegisterException $e) {
            return response()->json([
                'user' => null,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'user' => null,
                'message' => 'Erro ao registrar usuário'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/activate',
        summary: 'Ativar conta de usuário',
        description: 'Ativa a conta do usuário usando o token recebido por email',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', maxLength: 255, example: 'abc123def456'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuário ativado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuário ativado com sucesso'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Token inválido ou expirado'),
            new OA\Response(response: 422, description: 'Dados de validação inválidos'),
        ]
    )]
    public function activate(ActivateRequest $request): Response
    {
        try {
            $validated = $request->validated();
            $activate = new ActivateDTO($validated['token']);
            $this->authServices->activate($activate);
            return response()->json([
                'message' => 'Usuário ativado com sucesso'
            ], Response::HTTP_OK);
        } catch (ActivateException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao ativar usuário'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/forgot-password',
        summary: 'Solicitar recuperação de senha',
        description: 'Envia um email com token para redefinição de senha',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email de recuperação enviado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'E-mail de redefinição de senha enviado com sucesso'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Email não encontrado'),
            new OA\Response(response: 422, description: 'Dados de validação inválidos'),
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): Response
    {
        try {
            $validated = $request->validated();
            $forgot = new ForgotDTO($validated['email']);
            $this->authServices->forgotPassword($forgot);
            return response()->json([
                'message' => 'E-mail de redefinição de senha enviado com sucesso'
            ], Response::HTTP_OK);
        } catch (ForgotPasswordException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao enviar e-mail de redefinição de senha'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/reset-password',
        summary: 'Redefinir senha',
        description: 'Redefine a senha do usuário usando o token recebido por email',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'token'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'novaSenha123', minLength: 8),
                    new OA\Property(property: 'token', type: 'string', example: 'abc123def456'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Senha redefinida com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Senha redefinida com sucesso'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Token inválido ou expirado'),
            new OA\Response(response: 422, description: 'Dados de validação inválidos'),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): Response
    {
        try {
            $validated = $request->validated();
            $reset = new ResetPasswordDTO($validated['email'], $validated['password'], $validated['token']);
            $this->authServices->resetPassword($reset);
            return response()->json([
                'message' => 'Senha redefinida com sucesso'
            ], Response::HTTP_OK);
        } catch (ResetPasswordException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao redefinir senha'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Realizar logout',
        description: 'Revoga todos os tokens de autenticação do usuário',
        tags: ['Autenticação'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout realizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'null', example: null),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout realizado com sucesso'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function logout()
    {
        try {
            $user = Auth::user();
            $this->authServices->logout($user);
            return response()->json([
                'token' => null,
                'message' => 'Logout realizado com sucesso'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'token' => null,
                'message' => 'Erro ao realizar logout'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
