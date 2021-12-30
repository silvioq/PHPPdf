<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\TableFormatter,
    PHPPdf\Core\Document,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Node\Table\Row,
    PHPPdf\Core\Node\Table,
    PHPPdf\ObjectMother\TableObjectMother,
    PHPPdf\Core\Node\Table\Cell;
use PHPPdf\PHPUnit\Framework\TestCase;

class TableFormatterTest extends TestCase
{
    private TableFormatter    $formatter;
    private TableObjectMother $objectMother;

    protected function init(): void
    {
        $this->objectMother = new TableObjectMother($this);
    }

    public function setUp(): void
    {
        $this->formatter = new TableFormatter();
    }

    /**
     * @test
     * @dataProvider cellsWidthProvider
     */
    public function equalizeCells(array $cellsWidthInRows, array $minWidthsOfColumns, array $columnsWidths, array $columnsMarginsLeft, array $columnsMarginsRight, $tableWidth): void
    {
        $totalWidth      = array_sum($columnsWidths);
        $numberOfColumns = count($columnsWidths);

        $rows = [];
        foreach ($cellsWidthInRows as $widths) {
            $diffBetweenTableAndColumnsWidths = $tableWidth - $totalWidth - array_sum($columnsMarginsLeft) - array_sum($columnsMarginsRight);
            $translate                        = 0;
            $cells                            = [];
            foreach ($widths as $column => $width) {
                $bothMargins = $columnsMarginsLeft[$column] + $columnsMarginsRight[$column];
                $columnWidth = $columnsWidths[$column];
                $minWidth    = $minWidthsOfColumns[$column] + $bothMargins;
                $widthMargin = $columnWidth - $minWidth;

                if ($diffBetweenTableAndColumnsWidths < 0 && -$diffBetweenTableAndColumnsWidths >= $widthMargin) {
                    $columnWidth                      = $minWidth;
                    $diffBetweenTableAndColumnsWidths += $widthMargin;
                } elseif ($diffBetweenTableAndColumnsWidths < 0) {
                    $columnWidth                      += $diffBetweenTableAndColumnsWidths;
                    $diffBetweenTableAndColumnsWidths = 0;
                }

                $translate += $columnsMarginsLeft[$column];
                $cell      = $this->objectMother->getCellMockWithTranslateAndResizeExpectations($width, $columnWidth, $translate);
                $cell->expects($this->atLeastOnce())
                     ->method('getNumberOfColumn')
                     ->willReturn($column);
                $cells[]   = $cell;
                $translate += $columnWidth + $columnsMarginsRight[$column];
            }

            $row = $this->getMockBuilder(Row::class)
                        ->enableOriginalConstructor()
                        ->onlyMethods(['getChildren'])
                        ->getMock();
            $row->expects($this->atLeastOnce())
                ->method('getChildren')
                ->willReturn($cells);
            $rows[] = $row;
        }

        $table = $this->getMockBuilder(Table::class)
                      ->enableOriginalConstructor()
                      ->onlyMethods(['getChildren', 'getWidthsOfColumns', 'getMinWidthsOfColumns', 'getWidth', 'getMarginsLeftOfColumns', 'getMarginsRightOfColumns'])
                      ->getMock();
        $table->expects($this->atLeastOnce())
              ->method('getChildren')
              ->willReturn($rows);

        $table->expects($this->atLeastOnce())
              ->method('getWidthsOfColumns')
              ->willReturn($columnsWidths);

        $table->expects($this->atLeastOnce())
              ->method('getMinWidthsOfColumns')
              ->willReturn($minWidthsOfColumns);

        $table->expects($this->atLeastOnce())
              ->method('getWidth')
              ->willReturn($tableWidth);

        $table->expects($this->atLeastOnce())
              ->method('getMarginsLeftOfColumns')
              ->willReturn($columnsMarginsLeft);
        $table->expects($this->atLeastOnce())
              ->method('getMarginsRightOfColumns')
              ->willReturn($columnsMarginsRight);

        $this->formatter->format($table, $this->createDocumentStub());
    }

    public function cellsWidthProvider(): array
    {
        return [
            [
                [
                    [10, 20, 30],
                    [40, 10, 15],
                ],
                [0, 0, 0],
                [50, 20, 30],
                [0, 0, 0],
                [0, 0, 0],
                100,
            ],
            [
                [
                    [10, 20, 30],
                    [40, 10, 15],
                ],
                [5, 10, 0],
                [50, 20, 30],
                [0, 0, 0],
                [2, 2, 2],
                90,
            ],
        ];
    }
}
