<?php

namespace PHPPdf\Test\Core\Node\Paragraph;

use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Paragraph;
use PHPPdf\Core\Point,
    PHPPdf\Core\Document,
    PHPPdf\Core\Node\Paragraph\Line;

class LineTest extends \PHPPdf\PHPUnit\Framework\TestCase
{    
    /**
     * @test
     * @dataProvider textAlignProvider
     */
    public function applyAlignOnLineParts($paragraphFirstPoint, $width, $align, $widthOfParts, $expectedTranslation)
    {        
        $paragraph = $this->createParagraph($paragraphFirstPoint, $width, 0, 0, $align);
        
        $xTranslation = 0;
        $line = new Line($paragraph, $xTranslation, 0);
        
        foreach($widthOfParts as $widthOfPart)
        {
            $linePart = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\LinePart')
                             ->onlyMethods(array('getWidth'))
                             ->disableOriginalConstructor()
                             ->getMock();

            $linePart->expects($this->any())
                     ->method('getWidth')
                     ->willReturn($widthOfPart);
                     
            $line->addPart($linePart);
        }
        
        $line->format();
        
        $expectedXCoord = $paragraphFirstPoint->getX() + $expectedTranslation;
        $actualXCoord = $line->getFirstPoint()->getX();
        $this->assertEquals($expectedXCoord, $actualXCoord);
    }
    
    private function createParagraph($firstPoint, $width, $paddingLeft, $paddingRight, $align)
    {
        $paragraph = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph')
                          ->onlyMethods(array('getWidth', 'getParentPaddingLeft', 'getParentPaddingRight'))
                          ->getMock();
        $paragraph->getBoundary()->setNext($firstPoint)
                                 ->setNext($firstPoint->translate($width, 0));
        $paragraph->expects($this->any())
                  ->method('getWidth')
                  ->willReturn($width);
        $paragraph->setAttribute('text-align', $align);
        
        $paragraph->expects($this->any())
                  ->method('getParentPaddingLeft')
                  ->willReturn($paddingLeft);

        $paragraph->expects($this->any())
                  ->method('getParentPaddingRight')
                  ->willReturn($paddingRight);
                  
        return $paragraph;
    }
    
    public function textAlignProvider()
    {
        return array(
            array(Point::getInstance(20, 100), 400, Node::ALIGN_LEFT, array(100, 100, 100), 0),
            array(Point::getInstance(20, 100), 400, Node::ALIGN_RIGHT, array(100, 100, 100), 100),
            array(Point::getInstance(20, 100), 400, Node::ALIGN_CENTER, array(100, 100, 100), 50),
        );
    }
    
    /**
     * @test
     */
    public function firstPointIsTranslatedFirstPointOfParagraph()
    {
        $yTranslation = 21;
        $xTranslation = 10;
        $paragraph = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph')
                          ->onlyMethods(array('getFirstPoint'))
                          ->getMock();
                          
        $firstPoint = Point::getInstance(100, 100);
                          
        $paragraph->expects($this->once())
                  ->method('getFirstPoint')
                  ->willReturn($firstPoint);
                  
        $line = new Line($paragraph, $xTranslation, $yTranslation);
        
        $this->assertEquals(array(110, 79), $line->getFirstPoint()->toArray());
    }
    
    /**
     * @test
     */
    public function justifyLine()
    {
        $paragraphWidth = 100;

        $paragraph = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph')
                          ->onlyMethods(array('getWidth', 'getRecurseAttribute', 'getParentPaddingLeft', 'getParentPaddingRight'))
                          ->getMock();
        $paragraph->expects($this->atLeastOnce())
                  ->method('getWidth')
                  ->willReturn($paragraphWidth);
        $paragraph->expects($this->atLeastOnce())
                  ->method('getParentPaddingLeft')
                  ->willReturn(0);
        $paragraph->expects($this->atLeastOnce())
                  ->method('getParentPaddingRight')
                  ->willReturn(0);
        $paragraph->expects($this->atLeastOnce())
                  ->method('getWidth')
                  ->willReturn($paragraphWidth);
        $paragraph->expects($this->atLeastOnce())
                  ->method('getRecurseAttribute')
                  ->with('text-align')
                  ->willReturn(Node::ALIGN_JUSTIFY);
                          
        $line = new Line($paragraph, 0, 0);
        
        $linePartSizes = array(30, 50);
        $numberOfWordsPerPart = 5;
        $numberOfSpaces = count($linePartSizes) * $numberOfWordsPerPart - 1;
        $totalWidth = array_sum($linePartSizes);

        $expectedWordSpacing = ($paragraphWidth - $totalWidth)/$numberOfSpaces;        
        
        foreach($linePartSizes as $width)
        {
            $linePart = $this->getMockBuilder('PHPPdf\Core\Node\Paragraph\LinePart')
                             ->onlyMethods(array('setWordSpacing', 'getWidth', 'getNumberOfWords'))
                             ->disableOriginalConstructor()
                             ->getMock();
            $linePart->expects($this->atLeastOnce())
                     ->method('getWidth')
                     ->willReturn($width);
                     
            $linePart->expects($this->once())
                     ->method('setWordSpacing')
                     ->with($expectedWordSpacing);
                     
            $linePart->expects($this->atLeastOnce())
                     ->method('getNumberOfWords')
                     ->willReturn($numberOfWordsPerPart);
            $line->addPart($linePart);
        }
        
        $line->format();
    }
}
