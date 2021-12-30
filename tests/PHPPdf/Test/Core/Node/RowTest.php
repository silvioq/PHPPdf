<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\Table\Row;
use PHPPdf\Core\Node as Nodes;
use PHPPdf\Core\Node\Table;
use PHPPdf\Core\Node\Table\Cell;

class RowTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $row = null;

    public function setUp(): void
    {
        $this->row = new Row();
    }

    /**
     * @test
     *
     */
    public function addingInvalidChild()
    {
        $this->expectException(\InvalidArgumentException::class);
        $node = new Nodes\Container();
        $this->row->add($node);
    }

    /**
     * @test
     */
    public function addingValidChild()
    {
        $node = new Nodes\Table\Cell();
        $this->row->add($node);

        $this->assertTrue(count($this->row->getChildren()) > 0);
    }

    /**
     * @test
     */
    public function breakAt()
    {
        $boundary = $this->row->getBoundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, 0)
                 ->setNext(0, 0)
                 ->close();
        $this->row->setHeight(100);

        $this->assertNull($this->row->breakAt(50));
    }

    /**
     * @test
     */
    public function getHeightFromTable()
    {
        $tableMock = $this->getMockBuilder(Table::class)
                          ->addMethods(['getRowHeight'])
                          ->getMock();

        $rowHeight = 45;
        $tableMock->expects($this->once())
                  ->method('getRowHeight')
                  ->willReturn($rowHeight);

        $tableMock->add($this->row);

        $this->assertEquals($rowHeight, $this->row->getHeight());
    }

    /**
     * @test
     */
    public function getWidthFromTable()
    {
        $tableMock = $this->createPartialMock('PHPPdf\Core\Node\Table', [
            'getWidth',
        ]);

        $width = 200;
        $tableMock->expects($this->exactly(2))
                  ->method('getWidth')
                  ->willReturn($width);

        $tableMock->add($this->row);

        $this->assertEquals($width, $this->row->getWidth());
        $this->row->setWidth(5);
        $this->assertEquals($width, $this->row->getWidth());
    }

    /**
     * @test
     * @dataProvider colspanProvider
     */
    public function setNumberOfColumnForCells(array $colspans): void
    {
        $i = 0;
        foreach ($colspans as $colspan) {
            $cell = $this->getMockBuilder(Cell::class)
                         ->enableOriginalConstructor()
                         ->onlyMethods(['setNumberOfColumn', 'getColspan'])
                         ->getMock();
            $cell->expects($this->atLeastOnce())
                 ->method('getColspan')
                 ->willReturn($colspan);
            $cell->expects($this->once())
                 ->method('setNumberOfColumn')
                 ->with($i);

            $cells[] = $cell;
            $i       += $colspan;
        }

        foreach ($cells as $cell) {
            $this->row->add($cell);
        }
    }

    public function colspanProvider(): array
    {
        return [
            [
                [1, 1],
//                array(2, 1),
            ],
        ];
    }

    /**
     * @test
     */
    public function addTableAsListenerWhenCellHasAddedToRow()
    {
        $table = $this->createMock(Table::class);
        $cell  = $this->cellWithAddListenerExpectation($table, 2);

        $this->row->setParent($table);
        $this->row->add($cell);
    }

    private function cellWithAddListenerExpectation($listener, $expectedCalls = 1)
    {
        $cell = $this->getMockBuilder(Cell::class)
                     ->enableOriginalConstructor()
                     ->onlyMethods(['addListener'])
                     ->getMock();
        $cell->expects($this->exactly($expectedCalls))
             ->method('addListener')
             ->withConsecutive([$listener]);

        return $cell;
    }

    /**
     * @test
     */
    public function addRowAsListenerWhenCellHasAddedToRow(): void
    {
        $cell = $this->cellWithAddListenerExpectation($this->row, 1);

        $this->row->add($cell);
    }

    /**
     * @test
     * @dataProvider cellsHeightsProvider
     */
    public function setMaxHeightWhenRowIsNotifiedByCell(array $heights): void
    {
        $cells = $this->createMockedCellsWithHeights($heights);

        foreach ($cells as $cell) {
            $this->row->attributeChanged($cell, 'height', null);
        }

        $this->assertEquals(max($heights), $this->row->getMaxHeightOfCells());
    }

    public function cellsHeightsProvider(): array
    {
        return [
            [
                [10, 20, 30, 20, 10],
            ],
        ];
    }

    private function createMockedCellsWithHeights(array $heights): array
    {
        $cells = [];
        foreach ($heights as $height) {
            $cell = $this->getMockBuilder(Cell::class)
                         ->enableOriginalConstructor()
                         ->onlyMethods(['getHeight'])
                         ->getMock();
            $cell->expects($this->atLeastOnce())
                 ->method('getHeight')
                 ->willReturn($height);
            $cells[] = $cell;
        }

        return $cells;
    }

    /**
     * @test
     * @dataProvider cellsHeightsProvider
     */
    public function setMaxHeightWhileCellAdding(array $heights): void
    {
        $cells = $this->createMockedCellsWithHeights($heights);

        foreach ($cells as $cell) {
            $this->row->add($cell);
        }

        $this->assertEquals(max($heights), $this->row->getMaxHeightOfCells());
    }

    /**
     * @test
     * @dataProvider marginsDataProvider
     */
    public function setMaxVerticalMarginsWhileCellAdding(array $marginsTop, array $marginsBottom): void
    {
        $cells = $this->createMockedCellsWidthVerticalMargins($marginsTop, $marginsBottom);

        foreach ($cells as $cell) {
            $this->row->add($cell);
        }

        $this->assertEquals(max($marginsTop), $this->row->getMarginsTopOfCells());
        $this->assertEquals(max($marginsBottom), $this->row->getMarginsBottomOfCells());
    }

    public function marginsDataProvider(): array
    {
        return [
            [
                [10, 12, 5],
                [5, 1, 8],
            ],
        ];
    }

    private function createMockedCellsWidthVerticalMargins($marginsTop, $marginsBottom): array
    {
        $cells = [];

        for ($i = 0, $count = count($marginsTop); $i < $count; $i++) {
            $cell = $this->getMockBuilder(Cell::class)
                         ->enableOriginalConstructor()
                         ->onlyMethods(['getMarginTop', 'getMarginBottom'])
                         ->getMock();

            $cell->expects($this->atLeastOnce())
                 ->method('getMarginTop')
                 ->willReturn($marginsTop[$i]);
            $cell->expects($this->atLeastOnce())
                 ->method('getMarginBottom')
                 ->willReturn($marginsBottom[$i]);

            $cells[] = $cell;
        }

        return $cells;
    }
}
