<?php

namespace App\Http\Controllers\Api;

use App\Models\TrainingGame;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingGameRequest;
use App\Http\Resources\TrainingGameResource;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Training Games', description: 'PGN games attached to training sessions')]
class TrainingGameController extends Controller
{
    #[OA\Post(
        path: '/training-sessions/{training_session}/games',
        operationId: 'createTrainingGame',
        summary: 'Add a game to a training session',
        description: 'Only the creator of the session can add games. '
            . 'The PGN must be at most 50000 characters and contain a valid mainline.',
        tags: ['Training Games'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrainingGameInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Game added.',
                content: new OA\JsonContent(ref: '#/components/schemas/TrainingGameResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function store(StoreTrainingGameRequest $request, TrainingSession $training_session): JsonResponse
    {
        $game = $training_session->games()
            ->create($request->validated());

        return (new TrainingGameResource($game))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/training-sessions/{training_session}/games/{game}',
        operationId: 'deleteTrainingGame',
        summary: 'Delete a game from a training session',
        description: 'Only the creator of the session can delete its games.',
        tags: ['Training Games'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
            new OA\Parameter(ref: '#/components/parameters/GameId'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Game deleted.'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function destroy(TrainingSession $training_session, TrainingGame $game): Response
    {
        Gate::authorize('delete', $training_session);

        abort_unless(
            $training_session->games()->whereKey($game->id)->exists(),
            404
        );

        $game->delete();

        return response()->noContent();
    }
}