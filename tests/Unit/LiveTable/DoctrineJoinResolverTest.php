<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\LiveTable;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Neox\NeoxCrudBundle\LiveTable\DoctrineJoinResolver;
use PHPUnit\Framework\TestCase;

final class DoctrineJoinResolverTest extends TestCase
{
    public function testResolveFieldAddsStableAliasesForDotNotation(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn(new Expr());

        $qb = new QueryBuilder($em);

        $resolver = new DoctrineJoinResolver();

        $field = $resolver->resolveField($qb, 'e', 'owner.company.name', 'left');

        $this->assertSame('owner_company.name', $field);

        $joins = $qb->getDQLPart('join');
        $this->assertIsArray($joins);
        $this->assertArrayHasKey('e', $joins);
        $this->assertCount(1, $joins['e']);
        $this->assertSame('owner', $joins['e'][0]->getAlias());
        $this->assertSame('e.owner', $joins['e'][0]->getJoin());

        $this->assertArrayHasKey('owner', $joins);
        $this->assertCount(1, $joins['owner']);
        $this->assertSame('owner_company', $joins['owner'][0]->getAlias());
        $this->assertSame('owner.company', $joins['owner'][0]->getJoin());
    }

    public function testResolveFieldDoesNotDuplicateExistingJoins(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn(new Expr());

        $qb = new QueryBuilder($em);

        $resolver = new DoctrineJoinResolver();

        $resolver->resolveField($qb, 'e', 'owner.company.name', 'left');
        $resolver->resolveField($qb, 'e', 'owner.company.email', 'left');

        $joins = $qb->getDQLPart('join');
        $this->assertIsArray($joins);

        $this->assertArrayHasKey('e', $joins);
        $this->assertCount(1, $joins['e']);

        $this->assertArrayHasKey('owner', $joins);
        $this->assertCount(1, $joins['owner']);
    }
}
