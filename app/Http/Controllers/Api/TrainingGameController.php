<?php

namespace App\Http\Controllers\Api;

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
}