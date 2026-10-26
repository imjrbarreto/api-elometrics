<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        title: 'Elometrics API',
        description: 'API para la gestión de entrenamientos de ajedrez.'
    ),
    servers: [
        new OA\Server(
            url: 'http://127.0.0.1:8000/api/v1',
            description: 'API local'
        ),
    ]
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Get(
    path: '/up',
    summary: 'Comprobar que la aplicación responde',
    tags: ['Estado'],
    servers: [
        new OA\Server(url: 'http://127.0.0.1:8000'),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'La aplicación está operativa.'
        ),
    ]
)]
class ApiDocumentation
{
}