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
        $qb->select('e')->from('Dummy', 'e');

        $resolver = new DoctrineJoinResolver();

        $field = $resolver->resolveField($qb, 'e', 'owner.company.name', 'left');

        $this->assertSame('owner_company.name', $field);

        $joins = $qb->getDQLPart('join');
        $this->assertIsArray($joins);
        $flatJoins = [];
        foreach ($joins as $rootJoins) {
            foreach ($rootJoins as $j) {
                $flatJoins[] = $j;
            }
        }

        $this->assertCount(2, $flatJoins);

        $aliases = array_map(static fn ($j) => $j->getAlias(), $flatJoins);
        $exprs = array_map(static fn ($j) => $j->getJoin(), $flatJoins);

        $this->assertContains('owner', $aliases);
        $this->assertContains('owner_company', $aliases);
        $this->assertContains('e.owner', $exprs);
        $this->assertContains('owner.company', $exprs);
    }

    public function testResolveFieldDoesNotDuplicateExistingJoins(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn(new Expr());

        $qb = new QueryBuilder($em);
        $qb->select('e')->from('Dummy', 'e');

        $resolver = new DoctrineJoinResolver();

        $resolver->resolveField($qb, 'e', 'owner.company.name', 'left');
        $resolver->resolveField($qb, 'e', 'owner.company.email', 'left');

        $joins = $qb->getDQLPart('join');
        $this->assertIsArray($joins);

        $flatJoins = [];
        foreach ($joins as $rootJoins) {
            foreach ($rootJoins as $j) {
                $flatJoins[] = $j;
            }
        }

        $this->assertCount(2, $flatJoins);
    }
}
