<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\RowDimensionFormatter,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Document;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPPdf\Core\Node\Table\Row;
use PHPPdf\Core\Node\Table\Cell;

class RowDimensionFormatterTest extends TestCase
{
    private RowDimensionFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new RowDimensionFormatter();
    }

    /**
     * @test
     * @dataProvider heightProvider
     */
    public function changeRowsHeightIfMaxCellHeigtIsGreater($oldHeight, $height): void
    {
        $diff = $height - $oldHeight;

        $boundary = $this->getBoundaryMockWithEnlargeAsserts($diff);

        $row = $this->getRowMockWithHeightAsserts($boundary, $oldHeight, $height);

        $this->formatter->format($row, $this->createDocumentStub());
    }

    public function heightProvider(): array
    {
        return [
            [100, 150],
            [100, 80],
        ];
    }

    private function getBoundaryMockWithEnlargeAsserts($enlargeBy): Boundary|MockObject
    {
        $boundary = $this->createPartialMock(Boundary::class, ['pointTranslate']);

        $boundary->expects($this->exactly(2))
                 ->method('pointTranslate')
                 ->withConsecutive([2, 0, $enlargeBy], [3, 0, $enlargeBy])
                 ->willReturnOnConsecutiveCalls($this->returnValue($boundary));

        return $boundary;
    }

    private function getRowMockWithHeightAsserts($boundary, $oldHeight, $maxHeightOfCells, $expectedNewHeight = null): Row|MockObject
    {
        $row = $this->createPartialMock(Row::class, ['getBoundary', 'getHeight', 'setHeight', 'getMaxHeightOfCells', 'getChildren', 'getMarginsBottomOfCells', 'getMarginsTopOfCells']);

        $expectedNewHeight = $expectedNewHeight ?? $maxHeightOfCells;

        $row->expects($this->atLeastOnce())
            ->method('getBoundary')
            ->willReturn($boundary);
        $row->expects($this->atLeastOnce())
            ->method('getHeight')
            ->willReturn($oldHeight);
        $row->expects($this->once())
            ->method('setHeight')
            ->with($expectedNewHeight);
        $row->expects($this->atLeastOnce())
            ->method('getMaxHeightOfCells')
            ->willReturn($maxHeightOfCells);

        return $row;
    }

    /**
     * @test
     * @dataProvider marginsDataProvider
     */
    public function enlargeCellsToRowHeight($rowHeight, array $cellHeights, $marginTop, $marginBottom): void
    {
        $verticalMargins = $marginTop + $marginBottom;

        $cells = [];

        foreach ($cellHeights as $height) {
            $boundary = $this->getBoundaryMockWithEnlargeAsserts($rowHeight - $height);

            $cell = $this->createPartialMock(Cell::class, ['getHeight', 'setHeight', 'getBoundary']);

            $cell->expects($this->atLeastOnce())
                 ->method('getBoundary')
                 ->willReturn($boundary);
            $cell->expects($this->atLeastOnce())
                 ->method('getHeight')
                 ->willReturn($height);
            $cell->expects($this->once())
                 ->method('setHeight')
                 ->with($rowHeight);

            $cells[] = $cell;
        }

        $boundary = $this->getBoundaryMockWithEnlargeAsserts($verticalMargins);
        $row      = $this->getRowMockWithHeightAsserts($boundary, $rowHeight, $rowHeight, $rowHeight + $verticalMargins);
        $row->expects($this->atLeastOnce())
            ->method('getMarginsBottomOfCells')
            ->willReturn($marginBottom);
        $row->expects($this->atLeastOnce())
            ->method('getMarginsTopOfCells')
            ->willReturn($marginTop);

        $row->expects($this->atLeastOnce())
            ->method('getChildren')
            ->willReturn($cells);

        $this->formatter->format($row, $this->createDocumentStub());
    }

    public function marginsDataProvider(): array
    {
        return [
            [
                100, [30, 50], 10, 12,
            ],
            [
                100, [30, 50], 0, 0,
            ],
        ];
    }
}
