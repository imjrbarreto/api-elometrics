<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/*
 * Componentes reutilizables del spec: schemas, respuestas de error y parámetros de ruta.
 * Ubicación sugerida: app/OpenApi/Components.php
 *
 * Las clases están vacías a propósito: solo sirven de soporte para los atributos.
 * l5-swagger las encuentra porque escanea app/ (revisa 'annotations' en config/l5-swagger.php).
 */

// =====================================================================
// ERRORES
// =====================================================================

#[OA\Schema(
    schema: 'Message',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'No autorizado.'),
    ],
)]
final class MessageSchema {}

#[OA\Schema(
    schema: 'ValidationError',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The email field is required.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            example: ['email' => ['The email field is required.']],
        ),
    ],
)]
final class ValidationErrorSchema {}

// Respuestas de error reutilizables: en los endpoints se usan con
// new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated')

#[OA\Response(
    response: 'Unauthenticated',
    description: 'Token ausente, inválido o expirado.',
    content: new OA\JsonContent(ref: '#/components/schemas/Message'),
)]
final class UnauthenticatedResponse {}

#[OA\Response(
    response: 'Forbidden',
    description: 'No tienes permisos para esta acción.',
    content: new OA\JsonContent(ref: '#/components/schemas/Message'),
)]
final class ForbiddenResponse {}

#[OA\Response(
    response: 'NotFound',
    description: 'Recurso no encontrado.',
    content: new OA\JsonContent(ref: '#/components/schemas/Message'),
)]
final class NotFoundResponse {}

#[OA\Response(
    response: 'ValidationFailed',
    description: 'Datos de validación incorrectos.',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
)]
final class ValidationFailedResponse {}

#[OA\Response(
    response: 'Conflict',
    description: 'La operación entra en conflicto con el estado actual (por ejemplo, ya existe).',
    content: new OA\JsonContent(ref: '#/components/schemas/Message'),
)]
final class ConflictResponse {}

// =====================================================================
// PARÁMETROS DE RUTA (un solo nombre por recurso, igual al de la ruta)
// =====================================================================

#[OA\Parameter(
    parameter: 'WorkspaceId',
    name: 'workspace',
    in: 'path',
    required: true,
    description: 'ID del workspace',
    schema: new OA\Schema(type: 'integer', example: 1),
)]
final class WorkspaceIdParameter {}

#[OA\Parameter(
    parameter: 'TrainingSessionId',
    name: 'training_session',
    in: 'path',
    required: true,
    description: 'ID de la sesión de entrenamiento',
    schema: new OA\Schema(type: 'integer', example: 3),
)]
final class TrainingSessionIdParameter {}

#[OA\Parameter(
    parameter: 'UserId',
    name: 'user',
    in: 'path',
    required: true,
    description: 'ID del usuario',
    schema: new OA\Schema(type: 'integer', example: 2),
)]
final class UserIdParameter {}

// =====================================================================
// ENTIDADES
// `required` importa: lo que NO esté ahí se genera como opcional (`campo?: tipo`)
// en TypeScript, y tendrías que comprobar undefined en todo el front.
// `nullable: true` solo en lo que de verdad puede venir null.
// =====================================================================

#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Lucía Fernández'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'lucia@example.com'),
    ],
)]
final class UserSchema {}

#[OA\Schema(
    schema: 'AuthToken',
    required: ['token_type', 'access_token'],
    properties: [
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'access_token', type: 'string', example: 'TOKEN_DE_ACCESO'),
    ],
)]
final class AuthTokenSchema {}

// Respuesta de /register (201) y /login (200)
#[OA\Schema(
    schema: 'AuthResponse',
    required: ['data', 'token'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
        new OA\Property(property: 'token', ref: '#/components/schemas/AuthToken'),
    ],
)]
final class AuthResponseSchema {}

// Body de POST /register (refleja las reglas de RegisterRequest)
#[OA\Schema(
    schema: 'RegisterInput',
    required: ['name', 'email', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Lucía Fernández'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'lucia@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
    ],
)]
final class RegisterInputSchema {}

// Body de POST /login (refleja las reglas de LoginRequest)
#[OA\Schema(
    schema: 'LoginInput',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'lucia@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
    ],
)]
final class LoginInputSchema {}

#[OA\Schema(
    schema: 'UserResource',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
    ],
)]
final class UserResourceSchema {}

#[OA\Schema(
    schema: 'TrainingSession',
    required: ['id', 'user_id', 'title', 'category'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'user_id', type: 'integer', description: 'ID del creador de la sesión', example: 1),
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Finales de torres'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Actividad de la torre y del rey.'),
        new OA\Property(property: 'category', ref: '#/components/schemas/TrainingCategory'),
        new OA\Property(property: 'resource', type: 'string', nullable: true, example: 'https://lichess.org/study'),
    ],
)]
final class TrainingSessionSchema {}

// Categorías de una sesión: se define UNA vez y se referencia desde todos los schemas.
// AJUSTA estos valores a los que acepta tu validación.
#[OA\Schema(
    schema: 'TrainingCategory',
    type: 'string',
    enum: ['opening', 'middlegame', 'endgame', 'tactics'],
    example: 'endgame',
)]
final class TrainingCategorySchema {}

// Body de POST /training-sessions (refleja las reglas de StoreTrainingSessionRequest)
#[OA\Schema(
    schema: 'TrainingSessionInput',
    required: ['title', 'category'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Finales de torres'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: 'Actividad de la torre y ejercicios practicos.'),
        new OA\Property(property: 'category', ref: '#/components/schemas/TrainingCategory'),
        new OA\Property(property: 'resource', type: 'string', maxLength: 255, nullable: true, example: 'https://ejemplo.com'),
    ],
)]
final class TrainingSessionInputSchema {}

// Body de PATCH /training-sessions/{training_session}: mismos campos, todos opcionales
#[OA\Schema(
    schema: 'TrainingSessionPatch',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Entrenamiento actualizado'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: 'Preparación de aperturas'),
        new OA\Property(property: 'category', ref: '#/components/schemas/TrainingCategory'),
        new OA\Property(property: 'resource', type: 'string', maxLength: 255, nullable: true, example: 'https://lichess.org/study'),
    ],
)]
final class TrainingSessionPatchSchema {}

#[OA\Schema(
    schema: 'Workspace',
    // `role` NO está en `required` porque la API todavía no lo devuelve.
    // Cuando el Resource del workspace lo incluya, añádelo aquí.
    required: ['id', 'title', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Camino a FM'),
        new OA\Property(property: 'status', ref: '#/components/schemas/WorkspaceStatus'),
        new OA\Property(
            property: 'role',
            type: 'string',
            enum: ['owner', 'coach', 'student', 'viewer'],
            description: 'Rol del usuario autenticado en este workspace (pendiente de implementar)',
            example: 'owner',
        ),
    ],
)]
final class WorkspaceSchema {}

// Estados de un workspace: se define UNA vez y se referencia desde los demás schemas.
// AJUSTA estos valores a los que acepta tu validación.
#[OA\Schema(
    schema: 'WorkspaceStatus',
    type: 'string',
    enum: ['active', 'paused'],
    example: 'active',
)]
final class WorkspaceStatusSchema {}

// Body de POST /workspaces (refleja las reglas de StoreWorkspaceRequest)
#[OA\Schema(
    schema: 'WorkspaceInput',
    required: ['title', 'status'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Camino a FM'),
        new OA\Property(property: 'status', ref: '#/components/schemas/WorkspaceStatus'),
    ],
)]
final class WorkspaceInputSchema {}

// Body de PATCH /workspaces/{workspace}: mismos campos, todos opcionales
#[OA\Schema(
    schema: 'WorkspacePatch',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Workspace actualizado'),
        new OA\Property(property: 'status', ref: '#/components/schemas/WorkspaceStatus'),
    ],
)]
final class WorkspacePatchSchema {}

#[OA\Schema(
    schema: 'Member',
    required: ['id', 'name', 'email', 'role'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'Marcos Ruiz'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'marcos@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'coach', 'student', 'viewer'], example: 'student'),
    ],
)]
final class MemberSchema {}

#[OA\Schema(
    schema: 'TrainingSessionResponse',
    required: ['id', 'workspace_id', 'training_session_id', 'user_id', 'content'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'workspace_id', type: 'integer', example: 1),
        new OA\Property(property: 'training_session_id', type: 'integer', example: 3),
        new OA\Property(property: 'user_id', type: 'integer', description: 'ID del estudiante autor', example: 8),
        new OA\Property(property: 'content', type: 'string', example: 'Mi variante principal es 1.Cf5.'),
        // Solo viene en el listado de respuestas, por eso no está en `required`.
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
)]
final class TrainingSessionResponseSchema {}

// =====================================================================
// ENVOLTURAS { data: ... }
// =====================================================================

#[OA\Schema(
    schema: 'TrainingSessionResource',
    required: ['data'],
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/TrainingSession')],
)]
final class TrainingSessionResourceSchema {}

#[OA\Schema(
    schema: 'TrainingSessionList',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TrainingSession')),
    ],
)]
final class TrainingSessionListSchema {}

#[OA\Schema(
    schema: 'WorkspaceResource',
    required: ['data'],
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Workspace')],
)]
final class WorkspaceResourceSchema {}

#[OA\Schema(
    schema: 'WorkspaceList',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Workspace')),
    ],
)]
final class WorkspaceListSchema {}

#[OA\Schema(
    schema: 'MemberResource',
    required: ['data'],
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Member')],
)]
final class MemberResourceSchema {}

#[OA\Schema(
    schema: 'MemberList',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Member')),
    ],
)]
final class MemberListSchema {}

#[OA\Schema(
    schema: 'TrainingSessionResponseResource',
    required: ['data'],
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/TrainingSessionResponse')],
)]
final class TrainingSessionResponseResourceSchema {}

#[OA\Schema(
    schema: 'TrainingSessionResponseList',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TrainingSessionResponse')),
    ],
)]
final class TrainingSessionResponseListSchema {}