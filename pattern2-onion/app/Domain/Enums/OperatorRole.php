<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum OperatorRole: string
{
    case Staff = 'staff';
    case Manager = 'manager';
}
