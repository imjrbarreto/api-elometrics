<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Authentication', description: 'Endpoints for user and session management')]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/register',
        operationId: 'register',
        summary: 'Register a user and obtain a token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created. Returns the public user data and an access token.',
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
        summary: 'Log in and obtain a token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged in.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials.',
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
        summary: 'Get the authenticated user',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user data.',
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
        summary: 'Log out by revoking the current token',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Current token revoked. No content.'
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