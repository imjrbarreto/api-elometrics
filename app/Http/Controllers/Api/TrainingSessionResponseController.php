<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingSessionResponseRequest;
use App\Http\Resources\TrainingSessionResponseResource;
use App\Models\TrainingSession;
use App\Models\TrainingSessionResponse;
use App\Models\Workspace;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Training Session Responses', description: 'Student responses to training sessions linked to workspaces')]
class TrainingSessionResponseController extends Controller
{
    #[OA\Get(
        path: '/workspaces/{workspace}/training-sessions/{training_session}/responses',
        operationId: 'listTrainingSessionResponses',
        summary: 'List the responses to a linked training session',
        description: 'Available to any member of the workspace. '
            . 'Members who can respond (students) only see the other responses after submitting their own: '
            . 'until then the list is empty. '
            . 'The responses of former members of the workspace are kept and still listed.',
        tags: ['Training Session Responses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Responses listed.',
                content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionResponseList')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function index(Request $request, Workspace $workspace, TrainingSession $training_session): AnonymousResourceCollection
    {
        Gate::authorize('viewTrainingSessionResponses', $workspace);

        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        $responses = TrainingSessionResponse::query()
            ->where('workspace_id', $workspace->id)
            ->where('training_session_id', $training_session->id)
            ->with('user:id,name')
            ->orderBy('id')
            ->get();

        $mustRespondFirst = Gate::allows('respondToTrainingSession', $workspace)
            && ! $responses->contains('user_id', $request->user()->id);

        return TrainingSessionResponseResource::collection(
            $mustRespondFirst ? collect() : $responses
        );
    }

    #[OA\Post(
        path: '/workspaces/{workspace}/training-sessions/{training_session}/responses',
        operationId: 'createTrainingSessionResponse',
        summary: 'Respond to a linked training session',
        description: 'Only members with the training-sessions.respond permission (currently students) can respond. '
            . 'Each student can send a single response per session and workspace. '
            . 'The author is the authenticated user. The session must be linked to the workspace.',
        tags: ['Training Session Responses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionResponseInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Response created.',
                content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionResponseResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function store(StoreTrainingSessionResponseRequest $request, Workspace $workspace, TrainingSession $training_session): JsonResponse
    {
        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        $data = $request->validated();

        try {
            $response = TrainingSessionResponse::create([
                'workspace_id' => $workspace->id,
                'training_session_id' => $training_session->id,
                'user_id' => $request->user()->id,
                'content' => $data['content'],
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'content' => 'You have already responded to this session in this workspace.',
            ]);
        }

        return (new TrainingSessionResponseResource($response))
            ->response()
            ->setStatusCode(201);
    }
}