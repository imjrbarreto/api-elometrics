<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingSessionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingSessionController extends Controller
{
    public function store(StoreTrainingSessionRequest $request): JsonResponse
    {
        $trainingSession = $request->user()->trainingSessions()->create($request->validated());

        return response()->json([
            'data' => [
                'id' => $trainingSession->id,
                'title' => $trainingSession->title,
                'description' => $trainingSession->description,
                'category' => $trainingSession->category,
                'resource' => $trainingSession->resource,
            ]
        ], 201);
    }
}
