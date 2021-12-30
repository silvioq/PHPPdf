<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Node\Behaviour;

use PHPPdf\Core\Point,
    PHPPdf\ObjectMother\NodeObjectMother,
    PHPPdf\Core\Node\Container,
    PHPPdf\Core\Node\Behaviour\GoToInternal;
use PHPPdf\Exception\RuntimeException;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\Core\Node\NodeAware;

class GoToInternalTest extends TestCase
{
    private NodeObjectMother $objectMother;

    public function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    /**
     * @test
     */
    public function attachGoToActionToGraphicsContext(): void
    {
        $x      = 0;
        $y      = 500;
        $width  = 100;
        $height = 100;

        $firstPoint = Point::getInstance(400, 300);

        $destination = $this->getMockBuilder(Container::class)
                            ->onlyMethods(['getFirstPoint', 'getGraphicsContext', 'getNode'])
                            ->getMock();

        $destination->expects($this->atLeastOnce())
                    ->method('getFirstPoint')
                    ->willReturn($firstPoint);

        $destination->expects($this->atLeastOnce())
                    ->method('getNode')
                    ->willReturn($destination);

        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();

        $gc->expects($this->once())
           ->method('goToAction')
           ->with($gc, $x, $y, $x + $width, $y - $height, $firstPoint->getY());

        $destination->expects($this->atLeastOnce())
                    ->method('getGraphicsContext')
                    ->willReturn($gc);

        $nodeStub = $this->getNodeStub($x, $y, $width, $height);

        $behaviour = new GoToInternal($destination);

        $behaviour->attach($gc, $nodeStub);
    }

    private function getNodeStub($x, $y, $width, $height): Container
    {
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);

        $node = new Container();
        $this->invokeMethod($node, 'setBoundary', [$boundary]);

        return $node;
    }

    /**
     * @test
     *
     */
    public function throwExceptionIfDestinationIsEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        $destination = $this->getMockBuilder(NodeAware::class)
                            ->getMock();
        $destination->expects($this->once())
                    ->method('getNode')
                    ->willReturn(null);

        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();

        $behaviour = new GoToInternal($destination);

        $behaviour->attach($gc, new Container());
    }
}
