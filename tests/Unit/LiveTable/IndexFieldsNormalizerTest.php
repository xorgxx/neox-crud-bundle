<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Tests\Unit\LiveTable;

use Neox\NeoxCrudBundle\LiveTable\IndexFieldsNormalizer;
use PHPUnit\Framework\TestCase;

final class IndexFieldsNormalizerTest extends TestCase
{
    public function testNormalizeDefaultsToNonSearchableNonSortableWhenListIsStrings(): void
    {
        $n = new IndexFieldsNormalizer();

        $columns = $n->normalize(['name', 'createdAt'], []);

        $this->assertCount(2, $columns);
        $this->assertSame('name', $columns[0]['name']);
        $this->assertFalse($columns[0]['sortable']);
        $this->assertFalse($columns[0]['searchable']);
        $this->assertSame('name', $columns[0]['query_path']);
    }

    public function testNormalizeReadsSearchableAndFilterConfigFromOptions(): void
    {
        $n = new IndexFieldsNormalizer();

        $columns = $n->normalize(['owner.email', 'enabled'], [
            'owner.email' => [
                'query_path' => 'owner.email',
                'searchable' => true,
                'sortable' => true,
                'join' => 'inner',
            ],
            'enabled' => [
                'filter' => [
                    'type' => 'boolean',
                ],
            ],
        ]);

        $this->assertTrue($n->isSortable($columns, 'owner.email'));
        $this->assertSame('owner.email', $n->getQueryPath($columns, 'owner.email'));
        $this->assertSame('inner', $n->getJoinType($columns, 'owner.email'));
        $this->assertSame(['owner.email'], $n->getSearchableNames($columns));

        $this->assertSame(['enabled'], $n->getFilterableNames($columns));
        $this->assertSame(['type' => 'boolean'], $n->getFilterConfig($columns, 'enabled'));
    }
}
