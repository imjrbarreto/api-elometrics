<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/*
 * Reusable OpenAPI components: schemas, error responses and path parameters.
 * Suggested location: app/OpenApi/Components.php
 *
 * The classes are empty on purpose: they only hold the attributes.
 * The file is loaded through composer's "autoload-dev" > "files" so that
 * swagger-php can resolve the classes by reflection.
 */

// =====================================================================
// ERRORS
// =====================================================================

#[OA\Schema(
    schema: 'Message',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized.'),
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

// Reusable error responses. In the endpoints they are used with
// new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated')

#[OA\Response(
    response: 'Unauthenticated',
    description: 'Missing, invalid or expired token.',
    content: new OA\JsonContent(ref: '#/components/schemas/Message'),
)]
final class UnauthenticatedResponse {}

#[OA\Response(
    response: 'Forbidden',
    description: 'You do not have permission to perform this action.',
    content: new OA\JsonContent(ref: '#/components/schemas/Message'),
)]
final class ForbiddenResponse {}

#[OA\Response(
    response: 'NotFound',
    description: 'Resource not found.',
    content: new OA\JsonContent(ref: '#/components/schemas/Message'),
)]
final class NotFoundResponse {}

#[OA\Response(
    response: 'ValidationFailed',
    description: 'Validation failed.',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
)]
final class ValidationFailedResponse {}

#[OA\Response(
    response: 'Conflict',
    description: 'The request conflicts with the current state (for example, it already exists).',
    content: new OA\JsonContent(ref: '#/components/schemas/Message'),
)]
final class ConflictResponse {}

// =====================================================================
// PATH PARAMETERS (one name per resource, identical to the route's)
// =====================================================================

#[OA\Parameter(
    parameter: 'WorkspaceId',
    name: 'workspace',
    in: 'path',
    required: true,
    description: 'Workspace ID',
    schema: new OA\Schema(type: 'integer', example: 1),
)]
final class WorkspaceIdParameter {}

#[OA\Parameter(
    parameter: 'TrainingSessionId',
    name: 'training_session',
    in: 'path',
    required: true,
    description: 'Training session ID',
    schema: new OA\Schema(type: 'integer', example: 3),
)]
final class TrainingSessionIdParameter {}

#[OA\Parameter(
    parameter: 'UserId',
    name: 'user',
    in: 'path',
    required: true,
    description: 'User ID',
    schema: new OA\Schema(type: 'integer', example: 2),
)]
final class UserIdParameter {}

#[OA\Parameter(
    parameter: 'GameId',
    name: 'game',
    in: 'path',
    required: true,
    description: 'Training game ID',
    schema: new OA\Schema(type: 'integer', example: 5),
)]
final class GameIdParameter {}

// =====================================================================
// ENTITIES
// `required` matters: anything NOT listed there is generated as optional
// (`field?: type`) in TypeScript, and the front would have to check for
// undefined everywhere. `nullable: true` only on what can really be null.
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

// Minimal public data of a user (no email), used where other members' data is shown.
#[OA\Schema(
    schema: 'UserSummary',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 8),
        new OA\Property(property: 'name', type: 'string', example: 'Lucía Fernández'),
    ],
)]
final class UserSummarySchema {}

#[OA\Schema(
    schema: 'AuthToken',
    required: ['token_type', 'access_token'],
    properties: [
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'access_token', type: 'string', example: 'ACCESS_TOKEN'),
    ],
)]
final class AuthTokenSchema {}

// Response of /register (201) and /login (200)
#[OA\Schema(
    schema: 'AuthResponse',
    required: ['data', 'token'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
        new OA\Property(property: 'token', ref: '#/components/schemas/AuthToken'),
    ],
)]
final class AuthResponseSchema {}

// Body of POST /register (mirrors the rules of RegisterRequest)
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

// Body of POST /login (mirrors the rules of LoginRequest)
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
        new OA\Property(property: 'user_id', type: 'integer', description: 'ID of the session creator', example: 1),
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Rook endgames'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Rook and king activity.'),
        new OA\Property(property: 'category', ref: '#/components/schemas/TrainingCategory'),
        new OA\Property(property: 'resource', type: 'string', nullable: true, example: 'https://lichess.org/study'),
    ],
)]
final class TrainingSessionSchema {}

// Session categories: defined ONCE and referenced from all the schemas.
// ADJUST these values to the ones your validation accepts.
#[OA\Schema(
    schema: 'TrainingCategory',
    type: 'string',
    enum: ['opening', 'middlegame', 'endgame', 'tactics', 'strategy'],
    example: 'endgame',
)]
final class TrainingCategorySchema {}

// Body of POST /training-sessions (mirrors the rules of StoreTrainingSessionRequest)
#[OA\Schema(
    schema: 'TrainingSessionInput',
    required: ['title', 'category'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Rook endgames'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: 'Rook activity and practical exercises.'),
        new OA\Property(property: 'category', ref: '#/components/schemas/TrainingCategory'),
        new OA\Property(property: 'resource', type: 'string', format: 'uri', maxLength: 255, nullable: true, example: 'https://example.com'),
    ],
)]
final class TrainingSessionInputSchema {}

// Body of PATCH /training-sessions/{training_session}: same fields, all optional
#[OA\Schema(
    schema: 'TrainingSessionPatch',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Updated training'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: 'Opening preparation'),
        new OA\Property(property: 'category', ref: '#/components/schemas/TrainingCategory'),
        new OA\Property(property: 'resource', type: 'string', format: 'uri', maxLength: 255, nullable: true, example: 'https://lichess.org/study'),
    ],
)]
final class TrainingSessionPatchSchema {}

// Body of POST /workspaces/{workspace}/training-sessions (mirrors AttachTrainingSessionRequest)
#[OA\Schema(
    schema: 'LinkTrainingSessionInput',
    required: ['training_session_id'],
    properties: [
        new OA\Property(
            property: 'training_session_id',
            type: 'integer',
            description: 'ID of an existing session created by the authenticated user',
            example: 3,
        ),
    ],
)]
final class LinkTrainingSessionInputSchema {}

// A game (PGN) attached to a training session
#[OA\Schema(
    schema: 'TrainingGame',
    required: ['id', 'training_session_id', 'pgn'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 5),
        new OA\Property(property: 'training_session_id', type: 'integer', example: 3),
        new OA\Property(property: 'pgn', type: 'string', example: '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 *'),
    ],
)]
final class TrainingGameSchema {}

// Body of POST /training-sessions/{training_session}/games (mirrors StoreTrainingGameRequest)
#[OA\Schema(
    schema: 'TrainingGameInput',
    required: ['pgn'],
    properties: [
        new OA\Property(
            property: 'pgn',
            type: 'string',
            maxLength: 50000,
            description: 'Game in PGN format. It must contain a valid mainline.',
            example: '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 *',
        ),
    ],
)]
final class TrainingGameInputSchema {}

#[OA\Schema(
    schema: 'TrainingGameResource',
    required: ['data'],
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/TrainingGame')],
)]
final class TrainingGameResourceSchema {}

// A training session together with its games (detail endpoints only).
#[OA\Schema(
    schema: 'TrainingSessionDetail',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/TrainingSession'),
        new OA\Schema(
            required: ['games'],
            properties: [
                new OA\Property(
                    property: 'games',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TrainingGame'),
                ),
            ],
        ),
    ],
)]
final class TrainingSessionDetailSchema {}

#[OA\Schema(
    schema: 'TrainingSessionDetailResource',
    required: ['data'],
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/TrainingSessionDetail')],
)]
final class TrainingSessionDetailResourceSchema {}

#[OA\Schema(
    schema: 'Workspace',
    required: ['id', 'title', 'status', 'role'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Road to FM'),
        new OA\Property(property: 'status', ref: '#/components/schemas/WorkspaceStatus'),
        new OA\Property(
            property: 'role',
            type: 'string',
            enum: ['owner', 'coach', 'student', 'viewer'],
            description: 'Role of the authenticated user in this workspace',
            example: 'owner',
        ),
    ],
)]
final class WorkspaceSchema {}

// Workspace statuses: defined ONCE and referenced from the other schemas.
// ADJUST these values to the ones your validation accepts.
#[OA\Schema(
    schema: 'WorkspaceStatus',
    type: 'string',
    enum: ['active', 'paused', 'archived'],
    example: 'active',
)]
final class WorkspaceStatusSchema {}

// Body of POST /workspaces (mirrors the rules of StoreWorkspaceRequest)
#[OA\Schema(
    schema: 'WorkspaceInput',
    // `status` is optional: the API defaults it to `active`.
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Road to FM'),
        new OA\Property(property: 'status', ref: '#/components/schemas/WorkspaceStatus'),
    ],
)]
final class WorkspaceInputSchema {}

// Body of PATCH /workspaces/{workspace}: same fields, all optional
#[OA\Schema(
    schema: 'WorkspacePatch',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Updated workspace'),
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

// Body of POST /workspaces/{workspace}/members (mirrors StoreWorkspaceMemberRequest).
// `owner` cannot be assigned: the owner is the user who created the workspace.
#[OA\Schema(
    schema: 'MemberInput',
    required: ['email', 'role'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'marcos@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['coach', 'student', 'viewer'], example: 'student'),
    ],
)]
final class MemberInputSchema {}

#[OA\Schema(
    schema: 'TrainingSessionResponse',
    required: ['id', 'workspace_id', 'training_session_id', 'user_id', 'content'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'workspace_id', type: 'integer', example: 1),
        new OA\Property(property: 'training_session_id', type: 'integer', example: 3),
        new OA\Property(property: 'user_id', type: 'integer', description: 'ID of the student who wrote the response', example: 8),
        new OA\Property(property: 'content', type: 'string', example: 'My main line is 1.Nf5.'),
        // Only present in the list of responses, so it is not in `required`.
        new OA\Property(property: 'user', ref: '#/components/schemas/UserSummary'),
    ],
)]
final class TrainingSessionResponseSchema {}

// Body of POST /workspaces/{workspace}/training-sessions/{training_session}/responses
// (mirrors StoreTrainingSessionResponseRequest)
#[OA\Schema(
    schema: 'TrainingSessionResponseInput',
    required: ['content'],
    properties: [
        new OA\Property(property: 'content', type: 'string', minLength: 1, maxLength: 10000, example: 'My main line is 1.Nf5.'),
    ],
)]
final class TrainingSessionResponseInputSchema {}

// =====================================================================
// ENVELOPES { data: ... }
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