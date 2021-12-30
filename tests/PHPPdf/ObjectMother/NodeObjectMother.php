<?php

declare(strict_types=1);

namespace PHPPdf\ObjectMother;

use PHPPdf\Core\Boundary;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPUnit\Framework\TestCase;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\Node;

class NodeObjectMother
{
    private TestCase $test;

    public function __construct(TestCase $test)
    {
        $this->test = $test;
    }

    public function getPageMock($x, $y)
    {
        $gcMock = $this->test->getMockBuilder(GraphicsContext::class)
                             ->getMock();
        $gcMock->expects($this->test->once())
               ->method('drawPolygon')
               ->with($x, $y, GraphicsContext::SHAPE_DRAW_STROKE);

        return $this->getEmptyPageMock($gcMock);
    }

    public function getEmptyPageMock($graphicsContext)
    {
        $pageMock = $this->test->getMockBuilder(Page::class)
                               ->disableOriginalConstructor()
                               ->onlyMethods(['getGraphicsContext'])
                               ->getMock();

        $pageMock->expects($this->test->atLeastOnce())
                 ->method('getGraphicsContext')
                 ->willReturn($graphicsContext);

        return $pageMock;
    }

    public function getNodeMock($x, $y, $width, $height, $gc = null)
    {
        $boundaryMock = $this->getBoundaryStub($x, $y, $width, $height);

        $nodeMock = $this->test->getMockBuilder(Node::class)
                               ->enableOriginalConstructor()
                               ->onlyMethods(['getBoundary', 'getWidth', 'getHeight', 'getGraphicsContext'])
                               ->getMock();

        $nodeMock->expects($this->test->atLeastOnce())
                 ->method('getBoundary')
                 ->willReturn($boundaryMock);

        $nodeMock
            ->method('getWidth')
            ->willReturn($width);

        $nodeMock
            ->method('getHeight')
            ->willReturn($height);

        if ($gc) {
            $nodeMock->expects($this->test->atLeastOnce())
                     ->method('getGraphicsContext')
                     ->willReturn($gc);
        }

        return $nodeMock;
    }

    public function getBoundaryStub($x, $y, $width, $height): Boundary
    {
        $boundary = new Boundary();

        $boundary->setNext($x, $y)
                 ->setNext($x + $width, $y)
                 ->setNext($x + $width, $y - $height)
                 ->setNext($x, $y - $height)
                 ->close();

        return $boundary;
    }

    public function getNodeStub($x, $y, $width, $height): Container
    {
        $boundary = $this->getBoundaryStub($x, $y, $width, $height);
        $node     = new Container();

        $this->test->invokeMethod($node, 'setBoundary', [$boundary]);

        $node->setWidth($width);
        $node->setHeight($height);

        return $node;
    }
}
