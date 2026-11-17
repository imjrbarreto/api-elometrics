<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;


#[OA\Tag(name: 'Workspaces', description: 'Endpoints para la gestión de workspaces')]
class WorkspaceController extends Controller
{
    #[OA\Post(
        path: '/workspaces',
        operationId: 'createWorkspace',
        summary: 'Crear un nuevo workspace',
        tags: ['Workspaces'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/WorkspaceInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Workspace creado exitosamente.',
                content: new OA\JsonContent(ref: '#/components/schemas/WorkspaceResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $request->user()->workspaces()->create($request->validated());

        return (new WorkspaceResource($workspace))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/workspaces',
        operationId: 'listWorkspaces',
        summary: 'Listar los workspaces del usuario',
        tags: ['Workspaces'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de workspaces devuelto con éxito.',
                content: new OA\JsonContent(ref: '#/components/schemas/WorkspaceList')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
        ]
    )]
    public function list(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $workspaces = Workspace::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('members', function ($members) use ($userId) {
                        $members->where('users.id', $userId);
                    });
            })
            ->orderBy('id')
            ->get();

        return WorkspaceResource::collection($workspaces);
    }

    #[OA\Get(
        path: '/workspaces/{workspace}',
        operationId: 'getWorkspace',
        summary: 'Obtener el detalle de un workspace',
        tags: ['Workspaces'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle del workspace devuelto exitosamente.',
                content: new OA\JsonContent(ref: '#/components/schemas/WorkspaceResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('view', $workspace);

        return new WorkspaceResource($workspace);
    }

    #[OA\Patch(
        path: '/workspaces/{workspace}',
        operationId: 'updateWorkspace',
        summary: 'Actualizar un workspace',
        description: 'Solo el owner puede actualizar el workspace.',
        tags: ['Workspaces'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/WorkspacePatch')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workspace actualizado exitosamente.',
                content: new OA\JsonContent(ref: '#/components/schemas/WorkspaceResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('update', $workspace);

        $workspace->update($request->validated());

        return new WorkspaceResource($workspace);
    }

    #[OA\Delete(
        path: '/workspaces/{workspace}',
        operationId: 'deleteWorkspace',
        summary: 'Eliminar un workspace',
        description: 'Solo el owner puede eliminar el workspace.',
        tags: ['Workspaces'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Workspace eliminado exitosamente.'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function delete(Workspace $workspace): JsonResponse
    {
        Gate::authorize('delete', $workspace);

        $workspace->delete();

        return response()->json(null, 204);
    }
}