<?php
declare(strict_types=1);

namespace App\Factory;

use App\Repository\ChainPostRepository;
use App\Repository\PostRepository;
use App\Repository\PostRepositoryInterface;
use App\Repository\RedisPostRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class PostRepositoryFactory implements PostRepositoryFactoryInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(ManagerRegistry $registry, CacheItemPoolInterface $cache)
    {
        $this->registry = $registry;
        $this->cache = $cache;
    }

    public function getRepository(?string $choice = null): PostRepositoryInterface
    {
        switch ($choice)
        {
            case RedisPostRepository::class:
                return new RedisPostRepository($this->cache);

            case PostRepository::class:
                $em = $this->registry->getManager();
                return $em->getRepository('Post');

            case ChainPostRepository::class:
            default:
                $repo = new ChainPostRepository();
                $repo->add($this->getRepository(RedisPostRepository::class));
                $repo->add($this->getRepository(PostRepository::class));
                return $repo;
        }
    }
}
