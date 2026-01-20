<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\LiveTable;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class PerformanceOptimizer
{
    private array $localCache = [];

    public function __construct(
        private ?CacheInterface $cache = null,
    ) {
    }

    public function remember(string $key, callable $compute, int $ttlSeconds): mixed
    {
        $normalizedKey = $this->normalizeKey($key);

        if (array_key_exists($normalizedKey, $this->localCache)) {
            return $this->localCache[$normalizedKey];
        }

        if ($this->cache === null) {
            $value = $compute();
            $this->localCache[$normalizedKey] = $value;
            return $value;
        }

        $value = $this->cache->get($normalizedKey, function (ItemInterface $item) use ($compute, $ttlSeconds): mixed {
            $item->expiresAfter($ttlSeconds);

            return $compute();
        });

        $this->localCache[$normalizedKey] = $value;

        return $value;
    }

    public function hashKey(string $prefix, array $data): string
    {
        try {
            $encoded = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $encoded = serialize($data);
        }

        $safePrefix = preg_replace('/[^A-Za-z0-9_]/', '_', $prefix);

        return $safePrefix . '_' . sha1((string) $encoded);
    }

    private function normalizeKey(string $key): string
    {
        return 'neox_crud_' . sha1($key);
    }
}
