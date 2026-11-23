<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreWorkspaceMemberRequest;
use App\Support\WorkspaceRoles;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;



#[OA\Tag(name: 'Workspace Members', description: 'Endpoints for managing the members of a workspace')]
class WorkspaceMemberController extends Controller
{
    #[OA\Get(
        path: '/workspaces/{workspace}/members',
        operationId: 'listWorkspaceMembers',
        summary: 'List the members of a workspace',
        description: 'Available to any member of the workspace. The owner is included in the list.',
        tags: ['Workspace Members'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Members listed.',
                content: new OA\JsonContent(ref: '#/components/schemas/MemberList')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function index(Workspace $workspace): JsonResponse
    {
        Gate::authorize('viewMembers', $workspace);

        $owner = User::findOrFail($workspace->user_id);

        $members = $workspace->members()
            ->orderBy('users.id')
            ->get()
            ->prepend($owner)
            ->unique('id')
            ->values();

        return response()->json([
            'data' => $members->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $workspace->roleOf($member),
            ])->all(),
        ]);
    }

    #[OA\Post(
        path: '/workspaces/{workspace}/members',
        operationId: 'addWorkspaceMember',
        summary: 'Add a member to a workspace',
        description: 'Only the owner can add members. The user must already be registered. '
            . 'Returns 422 if the data is invalid, the email does not belong to a registered user '
            . 'or the user already belongs to the workspace.',
        tags: ['Workspace Members'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MemberInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Member added.',
                content: new OA\JsonContent(ref: '#/components/schemas/MemberResource')
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function store(StoreWorkspaceMemberRequest $request, Workspace $workspace): JsonResponse
    {
        $data = $request->validated();

        $member = User::where('email', $data['email'])
            ->firstOrFail();

        DB::transaction(function () use ($workspace, $member, $data) {
            if ($workspace->hasMember($member)) {
                throw ValidationException::withMessages([
                    'email' => 'The user already belongs to the workspace.',
                ]);
            }

            $workspace->members()->attach($member->id);

            WorkspaceRoles::within(
                $workspace,
                $member,
                fn (User $user) => $user->syncRoles([$data['role']])
            );
        });

        return response()->json([
            'data' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $workspace->roleOf($member),
            ],
        ], 201);
    }

    #[OA\Delete(
        path: '/workspaces/{workspace}/members/{user}',
        operationId: 'removeWorkspaceMember',
        summary: 'Remove a member from a workspace',
        description: 'The owner can remove any member. Any member can remove themselves (leave the workspace). '
            . 'The owner cannot be removed.',
        tags: ['Workspace Members'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/WorkspaceId'),
            new OA\Parameter(ref: '#/components/parameters/UserId'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Member removed. No content.'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function destroy(Workspace $workspace, User $user): Response
    {
        Gate::authorize('removeMember', [$workspace, $user]);

        abort_unless($workspace->hasMember($user), 404);

        DB::transaction(function () use ($workspace, $user) {
            $workspace->trainingSessions()->detach(
                $workspace->trainingSessions()
                    ->where('training_sessions.user_id', $user->id)
                    ->pluck('training_sessions.id')
            );
            
            WorkspaceRoles::within(
                $workspace,
                $user,
                fn (User $member) => $member->syncRoles([])
            );

            $workspace->members()->detach($user->id);
        });

        return response()->noContent();
    }
}