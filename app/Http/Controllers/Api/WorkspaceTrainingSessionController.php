<?php

namespace App\Http\Controllers\Api;

use App\Models\TrainingSession;
use App\Models\Workspace;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttachTrainingSessionRequest;
use App\Http\Resources\TrainingSessionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;
#[OA\Tag(name: 'Workspace Training Sessions', description: 'Consulta y gestión de vínculos entre workspaces y sesiones de entrenamiento reutilizables')]
class WorkspaceTrainingSessionController extends Controller
{

    #[OA\Get(
        path: '/workspaces/{workspace}/training-sessions',
        summary: 'Listar las sesiones vinculadas a un workspace',
        description: 'Disponible para owner, coach, student y viewer del workspace. Devuelve una lista vacía si no existen sesiones vinculadas.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                required: true,
                description: 'ID del workspace',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de sesiones vinculadas.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        example: 3
                                    ),
                                    new OA\Property(
                                        property: 'user_id',
                                        type: 'integer',
                                        description: 'ID del creador de la sesión',
                                        example: 1
                                    ),
                                    new OA\Property(
                                        property: 'title',
                                        type: 'string',
                                        example: 'Finales de torres'
                                    ),
                                    new OA\Property(
                                        property: 'description',
                                        type: 'string',
                                        nullable: true,
                                        example: 'Actividad de la torre y del rey.'
                                    ),
                                    new OA\Property(
                                        property: 'category',
                                        type: 'string',
                                        nullable: true,
                                        example: 'endgame'
                                    ),
                                    new OA\Property(
                                        property: 'resource',
                                        type: 'string',
                                        format: 'uri',
                                        nullable: true,
                                        example: 'https://lichess.org/study'
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado.'
            ),
            new OA\Response(
                response: 403,
                description: 'El usuario no pertenece al workspace.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace no encontrado.'
            ),
        ]
    )]
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('viewTrainingSessions', $workspace);

        $sessions = $workspace->trainingSessions()
            ->orderByDesc('training_sessions.id')
            ->get();

        return TrainingSessionResource::collection($sessions);
    }

    #[OA\Post(
        path: '/workspaces/{workspace}/training-sessions',
        summary: 'Vincular una sesión existente a un workspace',
        description: 'Permite al owner o coach vincular una sesión creada por él. Repetir la vinculación no crea duplicados.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                required: true,
                description: 'ID del workspace',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['training_session_id'],
                properties: [
                    new OA\Property(
                        property: 'training_session_id',
                        type: 'integer',
                        description: 'ID de una sesión existente creada por el usuario autenticado',
                        example: 3
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: 'Sesión vinculada correctamente o vinculación ya existente.'
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado.'
            ),
            new OA\Response(
                response: 403,
                description: 'El usuario no es owner o coach del workspace, o no es el creador de la sesión.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace no encontrado.'
            ),
            new OA\Response(
                response: 422,
                description: 'El identificador de sesión falta, no es válido o no corresponde a una sesión existente.'
            ),
        ]
    )]
    public function store(AttachTrainingSessionRequest $request,Workspace $workspace): Response 
    {
        $data = $request->validated();

        $session = TrainingSession::findOrFail(
            $data['training_session_id']
        );

        Gate::authorize(
            'attachTrainingSession',
            [$workspace, $session]
        );

        $workspace->trainingSessions()
            ->syncWithoutDetaching([$session->id]);

        return response()->noContent();
    }

    #[OA\Delete(
        path: '/workspaces/{workspace}/training-sessions/{training_session}',
        summary: 'Desvincular una sesión de un workspace',
        description: 'Disponible para owner y coach. Elimina únicamente la vinculación: conserva la sesión original y sus vínculos con otros workspaces.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                required: true,
                description: 'ID del workspace',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            ),
            new OA\Parameter(
                name: 'training_session',
                in: 'path',
                required: true,
                description: 'ID de la sesión que se quiere desvincular',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 3
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Sesión desvinculada correctamente.'
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado.'
            ),
            new OA\Response(
                response: 403,
                description: 'El usuario no es owner o coach del workspace.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace o sesión no encontrados, o la sesión no está vinculada al workspace indicado.'
            ),
        ]
    )]
    public function destroy(Workspace $workspace, TrainingSession $training_session): Response 
    {
        Gate::authorize('detachTrainingSession', $workspace);

        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        $workspace->trainingSessions()
            ->detach($training_session->id);

        return response()->noContent();
    }
}