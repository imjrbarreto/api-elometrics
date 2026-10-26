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

    
    #[OA\Get(
        path: '/workspaces/{workspace}',
        summary: 'Obtener el detalle de un workspace',
        tags: ['Workspaces'],
        security: [
            ['bearerAuth' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                description: 'ID del workspace',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle del workspace devuelto exitosamente.',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            'id' => 1,
                            'title' => 'Camino a FM',
                            'status' => 'active',
                        ]
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'No tienes permisos para ver este workspace.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace no encontrado.'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
        ]
    )]

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
