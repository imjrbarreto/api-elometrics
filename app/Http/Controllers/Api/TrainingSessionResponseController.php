<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingSessionResponseRequest;
use App\Models\TrainingSession;
use App\Models\TrainingSessionResponse;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Training Session Responses', description: 'Student responses to training sessions linked to workspaces')]
class TrainingSessionResponseController extends Controller
{
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

        return response()->json([
            'data' => [
                'id' => $response->id,
                'workspace_id' => $response->workspace_id,
                'training_session_id' => $response->training_session_id,
                'user_id' => $response->user_id,
                'content' => $response->content,
            ],
        ], 201);
    }
}