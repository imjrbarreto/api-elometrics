<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreWorkspaceMemberRequest;
use App\Support\WorkspaceRoles;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;



#[OA\Tag(name: 'Members', description: 'Endpoints para la gestión de integrantes dentro de un workspace')]
class WorkspaceMemberController extends Controller
{
    #[OA\Get(
        path: '/workspaces/{workspace}/members',
        summary: 'Listar miembros de un espacio de trabajo',
        tags: ['Workspace Members'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                description: 'ID del espacio de trabajo (Workspace)',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de miembros obtenida exitosamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                required: ['id', 'name', 'email', 'role'],
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com'),
                                    new OA\Property(property: 'role', type: 'string', example: 'admin')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado. El usuario no tiene permisos para ver los miembros de este workspace.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace no encontrado.'
            )
        ]
    )]
    public function index(Workspace $workspace): JsonResponse
    {
        Gate::authorize('viewMembers', $workspace);

        $owner = User::findOrFail($workspace->user_id);

        $members = $workspace->members()
            ->orderBy('users.id')
            ->get()
            ->prepend($owner)
            ->unique('id')
            ->values();

        return response()->json([
            'data' => $members->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $workspace->roleOf($member),
            ])->all(),
        ]);
    }

    #[OA\Post(
        path: '/workspaces/{workspace}/members',
        summary: 'Agregar un miembro a un espacio de trabajo',
        tags: ['Workspace Members'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                description: 'ID del espacio de trabajo (Workspace)',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'role'],
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'colaborador@example.com'
                    ),
                    new OA\Property(
                        property: 'role',
                        type: 'string',
                        example: 'editor'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Miembro agregado exitosamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 2),
                                new OA\Property(property: 'name', type: 'string', example: 'Carlos Gómez'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'colaborador@example.com'),
                                new OA\Property(property: 'role', type: 'string', example: 'editor'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace o Usuario (por email) no encontrado.'
            ),
            new OA\Response(
                response: 422,
                description: 'Datos de validación incorrectos (por ejemplo, si el usuario ya pertenece al workspace).'
            ),
        ]
    )]
    public function store(StoreWorkspaceMemberRequest $request, Workspace $workspace): JsonResponse 
    {
        $data = $request->validated();

        $member = User::where('email', $data['email'])
            ->firstOrFail();

        DB::transaction(function () use ($workspace, $member, $data) {
            if ($workspace->hasMember($member)) {
                throw ValidationException::withMessages([
                    'email' => 'El usuario ya pertenece al workspace.',
                ]);
            }

            $workspace->members()->attach($member->id);

            WorkspaceRoles::within(
                $workspace,
                $member,
                fn (User $user) => $user->syncRoles([$data['role']])
            );
        });

        return response()->json([
            'data' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $workspace->roleOf($member),
            ],
        ], 201);
    }

    #[OA\Delete(
        path: '/workspaces/{workspace}/members/{user}',
        summary: 'Remover un miembro de un espacio de trabajo',
        tags: ['Workspace Members'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                description: 'ID del espacio de trabajo (Workspace)',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'user',
                in: 'path',
                description: 'ID del usuario a remover del workspace',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2)
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Miembro removido con éxito, sin contenido de retorno.'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado. El usuario actual no tiene permisos para remover a este miembro.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace, Usuario no encontrado, o el usuario especificado no pertenece a este workspace.'
            )
        ]
    )]

    public function destroy(Workspace $workspace, User $user): Response
    {
        Gate::authorize('removeMember', [$workspace, $user]);

        abort_unless($workspace->hasMember($user), 404);

        DB::transaction(function () use ($workspace, $user) {
            WorkspaceRoles::within(
                $workspace,
                $user,
                fn (User $member) => $member->syncRoles([])
            );

            $workspace->members()->detach($user->id);
        });

        return response()->noContent();
    }
}
