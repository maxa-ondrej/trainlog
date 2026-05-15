<?php

declare(strict_types=1);

namespace App\Entity;

enum Role: string {
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';
}
