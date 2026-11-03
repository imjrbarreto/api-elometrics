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

    public function delete(TrainingSession $trainingSession): JsonResponse
    {
        Gate::authorize('delete', $trainingSession);

        $trainingSession->delete();

        return response()->json(null, 204);
    }
}
