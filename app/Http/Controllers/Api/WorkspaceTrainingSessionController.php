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


#[OA\Tag(name: 'Workspaces Training Sessions', description: 'Endpoints para la gestion de workspace-training-session')]
class WorkspaceTrainingSessionController extends Controller
{

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
        summary: 'Asociar una sesión de entrenamiento a un espacio de trabajo',
        tags: ['Workspaces Training Sessions'],
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
                required: ['training_session_id'],
                properties: [
                    new OA\Property(
                        property: 'training_session_id',
                        type: 'integer',
                        description: 'ID de la sesión de entrenamiento a asociar',
                        example: 5
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: 'Asociación realizada con éxito, sin contenido de retorno.'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado. El usuario no tiene permisos para realizar esta acción.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace o Training Session no encontrados.'
            ),
            new OA\Response(
                response: 422,
                description: 'Datos de validación incorrectos.'
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

    public function destroy(Workspace $workspace, TrainingSession $training_session): Response 
    {
        Gate::authorize('detachTrainingSession', $workspace);

        // Comprobar que está vinculada a este workspace.
        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        // Eliminar únicamente la vinculación.
        $workspace->trainingSessions()
            ->detach($training_session->id);

        return response()->noContent();
    }
}