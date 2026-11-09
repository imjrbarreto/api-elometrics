<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachTrainingSessionRequest;
use App\Models\TrainingSession;
use App\Models\Workspace;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class WorkspaceTrainingSessionController extends Controller
{
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
}