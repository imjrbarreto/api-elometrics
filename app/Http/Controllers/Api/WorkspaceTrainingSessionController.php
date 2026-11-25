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

#[OA\Tag(name: 'Workspace Training Sessions', description: 'Query and manage the links between workspaces and reusable training sessions')]
class WorkspaceTrainingSessionController extends Controller
{
    #[OA\Get(
        path: '/workspaces/{workspace}/training-sessions',
        operationId: 'listWorkspaceTrainingSessions',
        summary: 'List the training sessions linked to a workspace',
        description: 'Available to any member of the workspace. Returns an empty list if no sessions are linked.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Linked training sessions listed.',
                content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionList')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('viewTrainingSessionResponses', $workspace);

        $sessions = $workspace->trainingSessions()
            ->orderByDesc('training_sessions.id')
            ->get();

        return TrainingSessionResource::collection($sessions);
    }

    #[OA\Post(
        path: '/workspaces/{workspace}/training-sessions',
        operationId: 'linkTrainingSession',
        summary: 'Link an existing training session to a workspace',
        description: 'Only the owner or a coach of the workspace can link a session, and only one they created. '
            . 'Linking the same session again does not create duplicates.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LinkTrainingSessionInput')
        ),
        responses: [
            new OA\Response(response: 204, description: 'Session linked, or the link already existed.'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function store(AttachTrainingSessionRequest $request, Workspace $workspace): Response
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

    #[OA\Get(
        path: '/workspaces/{workspace}/training-sessions/{training_session}',
        operationId: 'getWorkspaceTrainingSession',
        summary: 'Get a training session linked to a workspace',
        description: 'Available to any member of the workspace.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Linked training session returned.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/TrainingSessionResource'
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(Workspace $workspace, TrainingSession $training_session): TrainingSessionResource 
    {
        Gate::authorize('viewTrainingSessions', $workspace);

        $session = $workspace->trainingSessions()
            ->with('games')
            ->whereKey($training_session->id)
            ->firstOrFail();

        return new TrainingSessionResource($session);
    }

    #[OA\Delete(
        path: '/workspaces/{workspace}/training-sessions/{training_session}',
        operationId: 'unlinkTrainingSession',
        summary: 'Unlink a training session from a workspace',
        description: 'The owner can unlink any session; a coach can unlink only the sessions they created. '
            . 'Only the link is removed: the session itself and its links to other workspaces are kept.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Session unlinked.'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function destroy(Workspace $workspace, TrainingSession $training_session): Response
    {
        Gate::authorize('detachTrainingSession', [$workspace, $training_session]);

        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        $workspace->trainingSessions()
            ->detach($training_session->id);

        return response()->noContent();
    }
}