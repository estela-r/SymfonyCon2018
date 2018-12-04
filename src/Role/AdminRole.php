<?php
declare(strict_types=1);

namespace App\Role;

class AdminRole extends Role
{
    public function getName()
    {
        return 'ROLE_ADMIN';
    }
}
