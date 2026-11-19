<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\Workspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class WorkspaceTrainingSessionExportController extends Controller
{
    #[OA\Get(
        path: '/workspaces/{workspace}/training-sessions/{training_session}/export',
        operationId: 'exportTrainingSession',
        summary: 'Download a linked training session as a PDF',
        description: 'Available to any member of the workspace. The session must be linked to the workspace.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
            new OA\Parameter(ref: '#/components/parameters/TrainingSessionId'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PDF of the selected training session.',
                headers: [
                    new OA\Header(
                        header: 'Content-Disposition',
                        description: 'Attachment with the file name, for example: attachment; filename=training-session-3.pdf',
                        schema: new OA\Schema(type: 'string')
                    ),
                ],
                content: [
                    new OA\MediaType(
                        mediaType: 'application/pdf',
                        schema: new OA\Schema(
                            type: 'string',
                            format: 'binary'
                        )
                    ),
                ]
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function __invoke(Workspace $workspace, TrainingSession $training_session): Response
    {
        Gate::authorize('exportTrainingSession', $workspace);

        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        return Pdf::loadView('pdf.training-session', [
            'workspace' => $workspace,
            'session' => $training_session->load('user'),
        ])
            ->setPaper('a4')
            ->download("training-session-{$training_session->id}.pdf");
    }
}