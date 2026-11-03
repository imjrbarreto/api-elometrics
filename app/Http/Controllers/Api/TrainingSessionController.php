<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingSessionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function list(Request $request): JsonResponse
    {
        $traininsSession = $request->user()->trainingSessions()->get();

        $formattedTraining = $traininsSession->map(function ($traininsSession) {
            return [
                'id' => $traininsSession->id,
                'title' => $traininsSession->title,
                'description' => $traininsSession->description,
                'category' => $traininsSession->category,
                'resource' => $traininsSession->resource,
            ];
        })->toArray();

        return response()->json([
            'data' => $formattedTraining,
        ]);
    }
}
