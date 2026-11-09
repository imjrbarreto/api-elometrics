<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

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
}
