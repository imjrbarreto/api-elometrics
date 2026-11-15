<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\Workspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class WorkspaceTrainingSessionExportController extends Controller
{
    public function __invoke(
        Workspace $workspace,
        TrainingSession $training_session
    ): Response {
        Gate::authorize('exportTrainingSession', $workspace);

        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        return Pdf::loadView('pdf.training-session', [
            'workspace' => $workspace,
            'session' => $training_session,
        ])
            ->setPaper('a4')
            ->download("training-session-{$training_session->id}.pdf");
    }
}