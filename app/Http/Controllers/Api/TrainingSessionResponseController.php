<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingSessionResponseRequest;
use App\Models\TrainingSession;
use App\Models\TrainingSessionResponse;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class TrainingSessionResponseController extends Controller
{
    public function store(StoreTrainingSessionResponseRequest $request, Workspace $workspace, TrainingSession $training_session): JsonResponse 
    {
        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        $data = $request->validated();

        $response = TrainingSessionResponse::create([
            'workspace_id' => $workspace->id,
            'training_session_id' => $training_session->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

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