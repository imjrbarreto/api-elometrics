<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingSessionRequest;
use App\Http\Requests\UpdateTrainingSessionRequest;
use App\Http\Resources\TrainingSessionResource;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Training Sessions', description: 'Endpoints for training session management')]
class TrainingSessionController extends Controller
{
    #[OA\Post(
        path: '/training-sessions',
        operationId: 'createTrainingSession',
        summary: 'Create a training session',
        tags: ['Training Sessions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Training session created.',
                content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function store(StoreTrainingSessionRequest $request): JsonResponse
    {
        $trainingSession = $request->user()->trainingSessions()->create($request->validated());

        return (new TrainingSessionResource($trainingSession))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/training-sessions',
        operationId: 'listTrainingSessions',
        summary: 'List the user\'s training sessions',
        tags: ['Training Sessions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Training sessions listed.',
                content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionList')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
        ]
    )]
    public function list(Request $request): AnonymousResourceCollection
    {
        return TrainingSessionResource::collection(
            $request->user()->trainingSessions()->get()
        );
    }

    #[OA\Get(
        path: '/training-sessions/{training_session}',
        operationId: 'getTrainingSession',
        summary: 'Get a training session',
        description: 'Only the creator of the session can view it.',
        tags: ['Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Training session returned.',
                content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(TrainingSession $trainingSession): TrainingSessionResource
    {
        Gate::authorize('view', $trainingSession);

        return new TrainingSessionResource($trainingSession);
    }

    #[OA\Patch(
        path: '/training-sessions/{training_session}',
        operationId: 'updateTrainingSession',
        summary: 'Update a training session',
        description: 'Only the creator of the session can update it.',
        tags: ['Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionPatch')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Training session updated.',
                content: new OA\JsonContent(ref: '#/components/schemas/TrainingSessionResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function update(UpdateTrainingSessionRequest $request, TrainingSession $trainingSession): TrainingSessionResource
    {
        Gate::authorize('update', $trainingSession);

        $trainingSession->update($request->validated());

        return new TrainingSessionResource($trainingSession);
    }

    #[OA\Delete(
        path: '/training-sessions/{training_session}',
        operationId: 'deleteTrainingSession',
        summary: 'Delete a training session',
        description: 'Only the creator of the session can delete it.',
        tags: ['Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Training session deleted.'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function delete(TrainingSession $trainingSession): JsonResponse
    {
        Gate::authorize('delete', $trainingSession);

        $trainingSession->delete();

        return response()->json(null, 204);
    }
}