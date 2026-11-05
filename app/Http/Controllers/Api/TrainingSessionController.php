<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingSessionRequest;
use App\Http\Requests\UpdateTrainingSessionRequest;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Training Sessions', description: 'Endpoints para la gestión de sesiones de entrenamiento')]
class TrainingSessionController extends Controller
{
    #[OA\Post(
        path: '/training-sessions',
        summary: 'Crear una nueva sesion de entrenamiento',
        tags: ['Training Sessions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'category'],
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        maxLength: 255,
                        example: 'Finales de torres'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        maxLength: 255,
                        example: 'Actividad de la torre y ejercicios practicos.'
                    ),
                    new OA\Property(
                        property: 'category',
                        type: 'string',
                        example: 'endgame'
                    ),
                    new OA\Property(
                        property: 'resource',
                        type: 'string',
                        format: 'uri',
                        example: 'https://ejemplo.com'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'TrainingSession creada exitosamente.',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            'id' => 1,
                            'title' => 'Finales de torres',
                            'description' => 'Actividad de la torre y ejercicios practicos.',
                            'category' => 'endgame',
                            'resource' => 'https://lichess.org/study/abcd1234',
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Datos de validación incorrectos.'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
        ]
    )]
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

    #[OA\Get(
        path: '/training-sessions',
        summary: 'Listar las sesiones de entrenamiento del usuario',
        tags: ['Training Sessions'],
        security: [
            ['bearerAuth' => []]
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de sesiones de entrenamiento devuelto con éxito.',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            [
                                'id' => 1,
                                'title' => 'Defensa Francesa',
                                'description' => 'Variante Clasica en la defensa francesa',
                                'category' => 'opening',
                                'resources' => 'http://example.com',
                            ],
                            [
                                'id' => 2,
                                'title' => 'Defensa Caro Kann',
                                'description' => 'Variante del avance en la defensa caro kann',
                                'category' => 'opening',
                                'resources' => 'http://example.com',
                            ],
                        ]
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $trainingSession = $request->user()->trainingSessions()->get();

        $formattedTraining = $trainingSession->map(function ($trainingSession) {
            return [
                'id' => $trainingSession->id,
                'title' => $trainingSession->title,
                'description' => $trainingSession->description,
                'category' => $trainingSession->category,
                'resource' => $trainingSession->resource,
            ];
        })->toArray();

        return response()->json([
            'data' => $formattedTraining,
        ]);
    }

    #[OA\Get(
        path: '/training-sessions/{training-session}',
        summary: 'Listar una sesion de entrenamiento',
        tags: ['Training Sessions'],
        security: [
            ['bearerAuth' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'training-session',
                in: 'path',
                description: 'ID de la sesion de entrenamiento',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle de la training session devuelta exitosamente.',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            [
                                'id' => 1,
                                'title' => 'Defensa Caro Kann',
                                'description' => 'Variante del avance en la defensa caro kann',
                                'category' => 'opening',
                                'resources' => 'http://example.com',
                            ],
                        ]
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'No tienes permisos para ver esta sesion de entrenamiento.'
            ),
            new OA\Response(
                response: 404,
                description: 'sesion de entrenamiento no encontrada.'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.'
            ),
        ]
    )]
    public function show(TrainingSession $trainingSession): JsonResponse
    {
        Gate::authorize('view', $trainingSession);

        return response()->json([
            'data' => [
                'id' => $trainingSession->id,
                'title' => $trainingSession->title,
                'description' => $trainingSession->description,
                'category' => $trainingSession->category,
                'resource' => $trainingSession->resource,
            ]
        ]);
    }

    #[OA\Patch(
        path: '/training-sessions/{training-session}',
        summary: 'Actualizar una sesion de entrenamiento',
        tags: ['Training Sessions'],
        security: [
            ['bearerAuth' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'training-session',
                in: 'path',
                description: 'ID de la training-session',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        maxLength: 255,
                        example: 'Entrenamiento actualizado'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        maxLength: 255,
                        example: 'Cambios de aperturas'
                    ),
                    new OA\Property(
                        property: 'category',
                        type: 'string',
                        example: 'opening'
                    ),
                    new OA\Property(
                        property: 'resources',
                        type: 'string',
                        example: 'http://example.com'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'TrainingSession actualizada exitosamente.',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            'id' => 1,
                            'name' => 'TrainingSession actualizado',
                            'description' => 'actualizado',
                            'category' => 'opening',
                            'resources' => 'http://example.com',
                        ],
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Datos de validación incorrectos.'),
            new OA\Response(response: 401,description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No autorizado.'),
            new OA\Response(response: 404, description: 'Workspace no encontrado.')
        ]
    )]
    public function update(UpdateTrainingSessionRequest $request, TrainingSession $trainingSession): JsonResponse
    {
        Gate::authorize('update', $trainingSession);

        $trainingSession->update($request->validated());

        return response()->json([
            'data' => [
                'id' => $trainingSession->id,
                'title' => $trainingSession->title,
                'description' => $trainingSession->description,
                'category' => $trainingSession->category,
                'resource' => $trainingSession->resource,
            ]
        ], 200);
    }

    #[OA\Delete(
        path: '/training-sessions/{training-session}',
        summary: 'Eliminar una sesion de entrenamiento',
        tags: ['Training Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'trainingSession',
                in: 'path',
                description: 'ID de la trainingSession a eliminar',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'TrainingSession eliminada exitosamente.'
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No autorizado.'),
            new OA\Response(response: 404, description: 'TrainingSession no encontrado.')
        ]
    )]
    public function delete(TrainingSession $trainingSession): JsonResponse
    {
        Gate::authorize('delete', $trainingSession);

        $trainingSession->delete();

        return response()->json(null, 204);
    }
}
