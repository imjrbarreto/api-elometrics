<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        title: 'Elometrics API',
        description: 'API for managing chess training.'
    ),
    servers: [
        new OA\Server(
            url: 'http://127.0.0.1:8000/api/v1',
            description: 'Local API'
        ),
    ]
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Tag(name: 'Health', description: 'Application health check')]
#[OA\Get(
    path: '/up',
    operationId: 'healthCheck',
    summary: 'Check that the application is running',
    tags: ['Health'],
    servers: [
        // /up lives outside the /api/v1 prefix.
        new OA\Server(url: 'http://127.0.0.1:8000'),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'The application is up.'
        ),
    ]
)]
class ApiDocumentation
{
}