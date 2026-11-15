<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_session_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workspace_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('training_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('content');

            $table->timestamps();

            $table->unique(
                ['workspace_id', 'training_session_id', 'user_id'],
                'session_response_context_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_responses');
    }
};