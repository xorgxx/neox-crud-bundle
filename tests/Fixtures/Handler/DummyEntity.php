<?php

declare(strict_types=1);

namespace Tests\Fixtures\Handler;

final class DummyEntity
{
    public int $id;
    public string $name = '';
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
