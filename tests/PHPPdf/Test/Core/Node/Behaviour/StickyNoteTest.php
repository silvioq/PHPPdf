<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Node\Behaviour;

use PHPPdf\Core\Node\Behaviour\StickyNote;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Node\Container;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Engine\GraphicsContext;

class StickyNoteTest extends TestCase
{
    private NodeObjectMother $objectMother;

    public function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    /**
     * @test
     */
    public function attachNote(): void
    {
        $x      = 10;
        $y      = 200;
        $width  = 100;
        $height = 200;

        $node = $this->getNodeStub($x, $y, $width, $height);

        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();

        $text = 'some text';

        $gc->expects($this->once())
           ->method('attachStickyNote')
           ->with($x, $y, $x + $width, $y - $height, $text);

        $stickyNote = new StickyNote($text);

        $stickyNote->attach($gc, $node);
    }

    private function getNodeStub($x, $y, $width, $height): Container
    {
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);

        $node = new Container();
        $this->invokeMethod($node, 'setBoundary', [$boundary]);

        return $node;
    }
}
