<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Autenticación', description: 'Endpoints para la gestión de usuarios y sesiones')]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/register',
        operationId: 'register',
        summary: 'Registrar un usuario y obtener un token',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario creado. Devuelve sus datos públicos y un token de acceso.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        $tokenResult = $user->createToken('Personal Access Token');

        return response()->json([
            'data' => new UserResource($user),
            'token' => [
                'token_type' => 'Bearer',
                'access_token' => $tokenResult->accessToken,
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/login',
        operationId: 'login',
        summary: 'Iniciar sesión y obtener un token',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión iniciada.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales incorrectas.',
                content: new OA\JsonContent(ref: '#/components/schemas/Message')
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $tokenResult = $user->createToken('Personal Access Token');

        return response()->json([
            'data' => new UserResource($user),
            'token' => [
                'token_type' => 'Bearer',
                'access_token' => $tokenResult->accessToken,
            ],
        ], 200);
    }

    #[OA\Get(
        path: '/users/me',
        operationId: 'getCurrentUser',
        summary: 'Obtener los datos del usuario autenticado',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Datos del usuario autenticado.',
                content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
        ]
    )]
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    #[OA\Post(
        path: '/logout',
        operationId: 'logout',
        summary: 'Cerrar sesión revocando el token actual',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Token actual revocado. Respuesta sin contenido.'
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
        ]
    )]
    public function logout(Request $request): Response
    {
        $request->user()->token()->revoke();

        return response()->noContent();
    }
}