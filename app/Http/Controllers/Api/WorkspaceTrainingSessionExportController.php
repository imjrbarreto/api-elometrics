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
        summary: 'Descargar una sesión vinculada como PDF',
        description: 'Disponible para todos los miembros del workspace.',
        tags: ['Workspace Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            ),
            new OA\Parameter(
                name: 'training_session',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 3
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PDF de la sesión seleccionada.',
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
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado.'
            ),
            new OA\Response(
                response: 403,
                description: 'El usuario no pertenece al workspace.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace o sesión inexistentes, o sesión no vinculada.'
            ),
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
            'session' => $training_session,
        ])
            ->setPaper('a4')
            ->download("training-session-{$training_session->id}.pdf");
    }
}