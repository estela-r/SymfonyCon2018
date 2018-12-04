<?php
declare(strict_types=1);

namespace App\Factory;

use App\Repository\PostRepositoryInterface;

interface PostRepositoryFactoryInterface
{
    public function getRepository(string $class): PostRepositoryInterface;
}
