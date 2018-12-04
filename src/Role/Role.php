<?php
declare(strict_types=1);

namespace App\Role;

abstract class Role
{
    public function __toString()
    {
        return $this->getName();
    }

    abstract public function getName(): string;
}
