<?php

namespace App\Http\Controllers\Auth;

use App\DTO\Auth\{ActivateDTO, LoginDTO, RegisterDTO};
use App\Exceptions\Auth\{ActivateException, LoginException, RegisterException};
use App\Http\Controllers\Controller;
use App\Http\Requests\{ActivateRequest, LoginRequest, RegisterRequest};
use App\Http\Resources\Auth\RegisterResource;
use App\Services\AuthServices;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServices $authServices
    ){}

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
