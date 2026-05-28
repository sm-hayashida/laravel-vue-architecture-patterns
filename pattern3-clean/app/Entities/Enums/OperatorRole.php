<?php

declare(strict_types=1);

namespace App\Entities\Enums;

enum OperatorRole: string
{
    case Staff = 'staff';
    case Manager = 'manager';
}
