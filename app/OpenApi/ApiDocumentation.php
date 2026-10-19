<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Elometrics API',
    description: 'API para la gestión de entrenamientos de ajedrez.'
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Servidor local'
)]
#[OA\Get(
    path: '/up',
    summary: 'Comprobar que la aplicación responde',
    tags: ['Estado'],
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