<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'training_session_workspace',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('workspace_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('training_session_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(
                    ['workspace_id', 'training_session_id'],
                    'session_workspace_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_workspace');
    }
};