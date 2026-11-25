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

class TrainingGameController extends Controller
{
    public function store(StoreTrainingGameRequest $request, TrainingSession $training_session): JsonResponse
    {
        $game = $training_session->games()
            ->create($request->validated());

        return (new TrainingGameResource($game))
            ->response()
            ->setStatusCode(201);
    }

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