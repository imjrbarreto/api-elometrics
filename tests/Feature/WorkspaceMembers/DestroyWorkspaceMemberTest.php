<?php

use Laravel\Passport\Passport;

it('allows the owner to remove a workspace member', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$student->id}")
    )->assertNoContent();

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    $this->assertDatabaseMissing('model_has_roles', [
        'workspace_id' => $workspace->id,
        'model_id' => $student->id,
        'model_type' => $student->getMorphClass(),
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
    ]);
});