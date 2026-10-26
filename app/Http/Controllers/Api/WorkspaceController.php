<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class WorkspaceController extends Controller
{
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $request->user()->workspaces()->create($request->validated());

        return response()->json([
            'data' => [
                'id' => $workspace->id,
                'title' => $workspace->title,
                'status' => $workspace->status,
            ]
        ], 201);
    }
}
