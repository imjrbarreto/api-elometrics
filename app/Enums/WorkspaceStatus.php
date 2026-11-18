<?php

namespace App\Enums;

enum WorkspaceStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';
}