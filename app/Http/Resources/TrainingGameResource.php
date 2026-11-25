<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'training_session_id' => $this->training_session_id,
            'pgn' => $this->pgn,
        ];
    }
}