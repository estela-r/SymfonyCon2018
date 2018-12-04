<?php
declare(strict_types=1);

namespace App\Role;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;

class RoleCollection
{
    /**
     * @var Role[]
     */
    private $elements = [];

    public function add($element)
    {
        $this->ensureIsRole($element);

        if ($this->contains($element)) {
            return;
        }

        return new RoleCollection(array_merge($this->elements, [$element]));
    }

    public function set($key, $value)
    {
        $this->ensureIsRole($value);

        return new RoleCollection(array_merge($this->elements, [$key => $value]));
    }

    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
        $this->forAll([$this, 'ensureIsRole']);
    }

    private function ensureIsRole($role): void
    {
        if (!$role instanceof Role) {
            throw new \TypeError('In a role collection, only have roles!');
        }
    }

    public function forAll(Closure $p)
    {
        foreach ($this->elements as $key => $element) {
            if ( ! $p($key, $element)) {
                return false;
            }
        }

        return true;
    }

    public function contains($element)
    {
        return in_array($element, $this->elements, true);
    }

    /** Other collection-y methods here :) */
}
