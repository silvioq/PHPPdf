<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\PageContext,
    PHPPdf\Core\Node\DynamicPage;

class PageContextTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function gettingNumberOfPages()
    {
        $numberOfPages = 5;
        $mock = $this->createPartialMock('PHPPdf\Core\Node\DynamicPage', array('getNumberOfPages'));
        $mock->expects($this->atLeastOnce())
             ->method('getNumberOfPages')
             ->willReturn($numberOfPages);

        $currentPageNumber = 3;
        $context = new PageContext($currentPageNumber, $mock);

        $this->assertEquals($numberOfPages, $context->getNumberOfPages());
        $this->assertEquals($currentPageNumber, $context->getPageNumber());
    }
}
