<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingSessionResponseRequest;
use App\Models\TrainingSession;
use App\Models\TrainingSessionResponse;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Training Session Responses', description: 'Respuestas de estudiantes a sesiones vinculadas a workspaces')]
class TrainingSessionResponseController extends Controller
{

    #[OA\Post(
        path: '/workspaces/{workspace}/training-sessions/{training_session}/responses',
        summary: 'Responder a una sesión vinculada',
        description: 'Requiere el permiso training-sessions.respond en el workspace. Cada estudiante puede enviar una única respuesta por sesión y workspace. El autor se obtiene del usuario autenticado.',
        tags: ['Training Session Responses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'workspace',
                in: 'path',
                required: true,
                description: 'ID del workspace',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            ),
            new OA\Parameter(
                name: 'training_session',
                in: 'path',
                required: true,
                description: 'ID de una sesión vinculada al workspace',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 3
                )
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['content'],
                properties: [
                    new OA\Property(
                        property: 'content',
                        type: 'string',
                        minLength: 1,
                        maxLength: 10000,
                        example: 'Mi variante principal es 1.Cf5.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Respuesta creada correctamente.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'id',
                                    type: 'integer',
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'workspace_id',
                                    type: 'integer',
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'training_session_id',
                                    type: 'integer',
                                    example: 3
                                ),
                                new OA\Property(
                                    property: 'user_id',
                                    type: 'integer',
                                    description: 'ID del estudiante autenticado',
                                    example: 8
                                ),
                                new OA\Property(
                                    property: 'content',
                                    type: 'string',
                                    example: 'Mi variante principal es 1.Cf5.'
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado.'
            ),
            new OA\Response(
                response: 403,
                description: 'El usuario no pertenece al workspace o no tiene el permiso para responder.'
            ),
            new OA\Response(
                response: 404,
                description: 'Workspace o sesión no encontrados, o la sesión no está vinculada al workspace.'
            ),
            new OA\Response(
                response: 422,
                description: 'Contenido ausente o inválido, o el estudiante ya respondió a esta sesión en este workspace.'
            ),
        ]
    )]
    public function store(StoreTrainingSessionResponseRequest $request, Workspace $workspace, TrainingSession $training_session): JsonResponse 
    {
        $isLinked = $workspace->trainingSessions()
            ->whereKey($training_session->id)
            ->exists();

        abort_unless($isLinked, 404);

        $data = $request->validated();

        $alreadyResponded = TrainingSessionResponse::query()
            ->where('workspace_id', $workspace->id)
            ->where('training_session_id', $training_session->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyResponded) {
            throw ValidationException::withMessages([
                'content' => 'Ya has respondido a esta sesión en este workspace.',
            ]);
        }

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