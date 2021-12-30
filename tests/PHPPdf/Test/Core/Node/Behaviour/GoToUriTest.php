<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Node\Behaviour;

use PHPPdf\Core\Node\Container;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Node\Behaviour\GoToUrl;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Engine\GraphicsContext;

class GoToUriTest extends TestCase
{
    private NodeObjectMother $objectMother;

    public function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    /**
     * @test
     */
    public function attachGoToUrlActionToGraphicsContext(): void
    {
        $x      = 10;
        $y      = 200;
        $width  = 50;
        $height = 20;

        $uri = 'http://google.com';

        $nodeStub = $this->getNodeStub($x, $y, $width, $height);

        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();

        $gc->expects($this->once())
           ->method('uriAction')
           ->with($x, $y, $x + $width, $y - $height, $uri);

        $behaviour = new GoToUrl($uri);

        $behaviour->attach($gc, $nodeStub);
    }

    private function getNodeStub($x, $y, $width, $height): Container
    {
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);

        $node = new Container();
        $this->invokeMethod($node, 'setBoundary', [$boundary]);

        return $node;
    }
}
