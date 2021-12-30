<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\TextPositionFormatter,
    PHPPdf\Core\Point,
    PHPPdf\Core\Document;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Boundary;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class TextPositionFormatterTest extends TestCase
{
    const TEXT_LINE_HEIGHT = 14;

    private TextPositionFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new TextPositionFormatter();
    }

    /**
     * @test
     */
    public function addPointsToBoundaryAccordingToLineSizes(): void
    {
        $mock = $this->getTextMock([50, 100], [0, 700]);

        $this->formatter->format($mock, $this->createDocumentStub());
    }

    private function getTextMock($lineSizes, $parentFirstPoint, $firstXCoord = null): MockObject|Text
    {
        $parentMock = $this->getMockBuilder(Node::class)
                           ->enableOriginalConstructor()
                           ->onlyMethods(['getStartDrawingPoint'])
                           ->getMock();
        $parentMock->expects($this->once())
                   ->method('getStartDrawingPoint')
                   ->willReturn([0, 700]);

        $mock = $this->getMockBuilder(Text::class)
                     ->enableOriginalConstructor()
                     ->onlyMethods([
                                       'getParent',
                                       'getLineHeightRecursively',
                                       'getStartDrawingPoint',
                                       'getBoundary',
                                   ])
                     ->addMethods(['getLineSizes'])
                     ->getMock();

        $mock->expects($this->atLeastOnce())
             ->method('getParent')
             ->willReturn($parentMock);

        $boundaryMock = $this->createPartialMock(Boundary::class, [
            'getFirstPoint',
            'setNext',
            'close',
        ]);

        $firstXCoord = $firstXCoord ?: $parentFirstPoint[0];
        $boundaryMock->expects($this->atLeastOnce())
                     ->method('getFirstPoint')
                     ->willReturn(Point::getInstance($firstXCoord, $parentFirstPoint[1]));

        $this->addBoundaryPointsAsserts($boundaryMock, $lineSizes, $parentFirstPoint[1]);

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->willReturn($boundaryMock);

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->willReturn($boundaryMock);

        $mock->expects($this->once())
             ->method('getLineHeightRecursively')
             ->willReturn(self::TEXT_LINE_HEIGHT);

        $mock->expects($this->once())
             ->method('getLineSizes')
             ->willReturn($lineSizes);

        return $mock;
    }

    private function addBoundaryPointsAsserts($boundaryMock, $lineSizes, $firstYCoord): void
    {
        $at                = 1;
        $expectedArguments = [];
        foreach ($lineSizes as $i => $size) {
            $yCoord                   = $firstYCoord - self::TEXT_LINE_HEIGHT * $i;
            $expectedArguments[$at++] = [
                $size, $yCoord,
            ];

            if (isset($lineSizes[$i + 1])) {
                $expectedArguments[$at++] = [
                    $size, $yCoord - self::TEXT_LINE_HEIGHT,
                ];
            }
        }

        $boundaryMock
            ->method('setNext')
            ->withConsecutive(...$expectedArguments);

        $boundaryMock->expects($this->once())
                     ->method('close');
    }
}
