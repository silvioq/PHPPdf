<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\Table\Cell;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Table;
use PHPPdf\Core\Node\Listener;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Node\Table\Row;

class CellTest extends TestCase
{
    private Cell $cell;

    public function setUp(): void
    {
        $this->cell = new Cell();
    }

    public function testUnmodifableFloat(): void
    {
        $this->assertEquals(Node::FLOAT_LEFT, $this->cell->getFloat());
        $this->cell->setFloat(Node::FLOAT_RIGHT);
        $this->assertEquals(Node::FLOAT_LEFT, $this->cell->getFloat());
    }

    public function testDefaultWidth(): void
    {
        $this->assertSame($this->cell->getWidth(), 0);
    }

    public function testTableGetter(): void
    {
        $table = $this->createMock(Table::class);
        $row   = $this->createMock(Row::class);

        //internally in Node class is used $parent propery (not getParent() method) due to performance
        $this->writeAttribute($row, 'parent', $table);

        $this->cell->setParent($row);

        $this->assertSame($table, $this->cell->getTable());
    }

    public function testNotifyListenersWhenAttributeHasChanged(): void
    {
        $listener = $this->createPartialMock(Listener::class, ['attributeChanged', 'parentBind']);


        $listener->expects($this->exactly(2))
                 ->id('1')
                 ->method('attributeChanged')
                 ->withConsecutive([$this->cell, 'width', null], [$this->cell, 'width', 100]);


        $listener->expects($this->once())
                 ->after('1')
                 ->method('parentBind')
                 ->with($this->cell);

        $this->cell->addListener($listener);

        $this->cell->setAttribute('width', 100);
        $this->cell->setAttribute('width', 200);
        $this->cell->setParent(new Cell());
    }
}
