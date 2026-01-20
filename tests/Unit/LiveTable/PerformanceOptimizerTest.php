<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\LiveTable;

use Neox\NeoxCrudBundle\LiveTable\PerformanceOptimizer;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class PerformanceOptimizerTest extends TestCase
{
    public function testRememberCachesValueInLocalCache(): void
    {
        $optimizer = new PerformanceOptimizer(null);

        $calls = 0;
        $compute = function () use (&$calls): int {
            ++$calls;
            return 123;
        };

        $this->assertSame(123, $optimizer->remember('k', $compute, 10));
        $this->assertSame(123, $optimizer->remember('k', $compute, 10));
        $this->assertSame(1, $calls);
    }

    public function testRememberUsesSharedCacheWhenAvailable(): void
    {
        $store = [];

        $cache = new class($store) implements CacheInterface {
            public function __construct(
                private array &$store,
            ) {
            }

            public function get(string $key, callable $callback, float $beta = null, array &$metadata = null): mixed
            {
                if (array_key_exists($key, $this->store)) {
                    return $this->store[$key];
                }

                $item = new class($key) implements ItemInterface {
                    public function __construct(private string $key)
                    {
                    }

                    public function getKey(): string
                    {
                        return $this->key;
                    }

                    public function get(): mixed
                    {
                        return null;
                    }

                    public function isHit(): bool
                    {
                        return false;
                    }

                    public function set(mixed $value): static
                    {
                        return $this;
                    }

                    public function expiresAt(?\DateTimeInterface $expiration): static
                    {
                        return $this;
                    }

                    public function expiresAfter(\DateInterval|int|null $time): static
                    {
                        return $this;
                    }

                    public function tag(string|iterable $tags): static
                    {
                        return $this;
                    }

                    public function getMetadata(): array
                    {
                        return [];
                    }
                };

                $value = $callback($item);
                $this->store[$key] = $value;

                return $value;
            }

            public function delete(string $key): bool
            {
                unset($this->store[$key]);
                return true;
            }
        };

        $optimizer = new PerformanceOptimizer($cache);

        $calls = 0;
        $compute = function () use (&$calls): string {
            ++$calls;
            return 'abc';
        };

        $this->assertSame('abc', $optimizer->remember('k', $compute, 10));
        $this->assertSame('abc', $optimizer->remember('k', $compute, 10));
        $this->assertSame(1, $calls);

        $other = new PerformanceOptimizer($cache);
        $this->assertSame('abc', $other->remember('k', $compute, 10));
        $this->assertSame(1, $calls);
    }

    public function testHashKeyIsStableForSamePayload(): void
    {
        $optimizer = new PerformanceOptimizer(null);

        $k1 = $optimizer->hashKey('p', ['a' => 1, 'b' => 2]);
        $k2 = $optimizer->hashKey('p', ['a' => 1, 'b' => 2]);

        $this->assertSame($k1, $k2);
    }
}
