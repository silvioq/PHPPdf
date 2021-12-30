<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\TableColumnFormatter,
    PHPPdf\ObjectMother\TableObjectMother,
    PHPPdf\Core\Document;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Node\Table;
use PHPPdf\Core\Node\Table\Row;

class TableColumnFormatterTest extends TestCase
{
    private TableColumnFormatter $formatter;
    private TableObjectMother    $objectMother;

    protected function init(): void
    {
        $this->objectMother = new TableObjectMother($this);
    }

    public function setUp(): void
    {
        $this->formatter = new TableColumnFormatter();
    }

    /**
     * @test
     * @dataProvider columnsDataProvider
     */
    public function spreadEventlyColumnsWidth(array $cellsInRowsWidths, array $columnsWidths, $tableWidth): void
    {
        $table              = $this->createPartialMock(Table::class, ['reduceColumnsWidthsByMargins', 'getWidthsOfColumns', 'getChildren', 'getWidth', 'getNumberOfColumns', 'getMarginsLeftOfColumns', 'getMarginsRightOfColumns', 'convertRelativeWidthsOfColumns']);
        $totalColumnsWidth  = array_sum($columnsWidths);
        $numberOfColumns    = count($columnsWidths);
        $enlargeColumnWidth = ($tableWidth - $totalColumnsWidth) / $numberOfColumns;

        $rows = [];
        foreach ($cellsInRowsWidths as $cellsWidths) {
            $cells = [];
            foreach ($cellsWidths as $column => $width) {
                $cell = $this->objectMother->getCellMockWithResizeExpectations($width, $columnsWidths[$column] + $enlargeColumnWidth, false);
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->willReturn($column);
                $cells[] = $cell;
            }

            $row = $this->createPartialMock(Row::class, ['getChildren']);
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->willReturn($cells);

            $rows[] = $row;
        }

        $table->expects($this->once())
              ->id('convertColumns')
              ->method('convertRelativeWidthsOfColumns');

        $table->expects($this->once())
              ->id('reduceColumns')
              ->after('convertColumns')
              ->method('reduceColumnsWidthsByMargins');

        $table->expects($this->atLeastOnce())
              ->method('getChildren')
              ->after('reduceColumns')
              ->willReturn($rows);

        $table->expects($this->atLeastOnce())
              ->method('getWidth')
              ->after('reduceColumns')
              ->willReturn($tableWidth);

        $table->expects($this->atLeastOnce())
              ->method('getWidthsOfColumns')
              ->after('reduceColumns')
              ->willReturn($columnsWidths);

        $table->expects($this->atLeastOnce())
              ->method('getNumberOfColumns')
              ->after('reduceColumns')
              ->willReturn(count($columnsWidths));

        $margins = array_fill(0, $numberOfColumns, 0);
        $table->expects($this->atLeastOnce())
              ->method('getMarginsLeftOfColumns')
              ->after('reduceColumns')
              ->willReturn($margins);

        $table->expects($this->atLeastOnce())
              ->method('getMarginsRightOfColumns')
              ->after('reduceColumns')
              ->willReturn($margins);

        $this->formatter->format($table, $this->createDocumentStub());
    }

    public function columnsDataProvider()
    {
        return [
            [
                [
                    [10, 20, 15],
                    [5, 10, 10],
                ],
                [10, 20, 15],
                5,
                50,
            ],
        ];
    }
}
