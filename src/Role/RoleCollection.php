<?php
declare(strict_types=1);

namespace App\Role;

use Doctrine\Common\Collections\ArrayCollection;

class RoleCollection extends ArrayCollection
{
    public function add($element)
    {
        $this->ensureIsRole($element);

        if ($this->contains($element)) {
            return;
        }

        return parent::add($element);
    }

    public function set($key, $value)
    {
        $this->ensureIsRole($value);
        parent::set($key, $value);
    }

    public function __construct(array $elements = [])
    {
        parent::__construct($elements);
        $this->forAll([$this, 'ensureIsRole']);
    }

    private function ensureIsRole($role): void
    {
        if (!$role instanceof Role) {
            throw new \TypeError('In a role collection, only have roles!');
        }
    }
}
