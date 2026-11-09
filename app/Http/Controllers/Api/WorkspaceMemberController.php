<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreWorkspaceMemberRequest;
use App\Support\WorkspaceRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkspaceMemberController extends Controller
{
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
                'username' => $member->username,
                'role' => $workspace->roleOf($member),
            ])->all(),
        ]);
    }

    public function store(StoreWorkspaceMemberRequest $request, Workspace $workspace): JsonResponse 
    {
        $data = $request->validated();

        $member = User::where('email', $data['email'])
            ->firstOrFail();

        DB::transaction(function () use ($workspace, $member, $data) {
            if ($workspace->hasMember($member)) {
                throw ValidationException::withMessages([
                    'email' => 'El usuario ya pertenece al workspace.',
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
}
