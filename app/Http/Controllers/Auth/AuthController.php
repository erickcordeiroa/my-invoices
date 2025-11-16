<?php

namespace App\Http\Controllers\Auth;

use App\DTO\Auth\LoginDTO;
use App\Exceptions\Auth\LoginException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthServices;
use Illuminate\Http\Request;
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

    public function register(Request $request)
    {
        //
    }

    public function logout(Request $request)
    {
        //
    }
}
