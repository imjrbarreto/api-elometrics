<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;


#[OA\Tag(name: 'Workspaces', description: 'Endpoints para la gestión de workspaces')]
class WorkspaceController extends Controller
{
    #[OA\Post(
        path: '/workspaces',
        summary: 'Crear un nuevo workspace',
        tags: ['Workspaces'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'status'],
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        maxLength: 255,
                        example: 'Workspace 1'
                    ),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        example: 'active'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Workspace creado exitosamente.',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            'id' => 1,
                            'name' => 'Workspace 1',
                            'email' => 'active',
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Datos de validación incorrectos.'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
        ]
    )]
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $request->user()->workspaces()->create($request->validated());

        return response()->json([
            'data' => [
                'id' => $workspace->id,
                'title' => $workspace->title,
                'status' => $workspace->status,
            ]
        ], 201);
    }

    public function show(Workspace $workspace): JsonResponse
    {
        Gate::authorize('view', $workspace);

        return response()->json([
            'data' => [
                'id' => $workspace->id,
                'title' => $workspace->title,
                'status' => $workspace->status,
            ]
        ]);
    }
}
