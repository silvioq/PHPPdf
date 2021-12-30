<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\ZF\Image;
use PHPPdf\Core\UnitConverter;
use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\PHPUnit\Framework\TestCase;
use ZendPdf\PdfDocument;

class ImageTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(PdfDocument::class, true)) {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }
    }

    /**
     * @test
     */
    public function createImageObject(): void
    {
        $image = new Image(TEST_RESOURCES_DIR.'/domek.png');

        $zendImage = $image->getWrappedImage();

        $this->assertEquals($zendImage->getPixelHeight(), $image->getOriginalHeight());
        $this->assertEquals($zendImage->getPixelWidth(), $image->getOriginalWidth());
    }

    /**
     * @test
     *
     */
    public function throwExceptionOnUnexistedImage(): void
    {
        $this->expectException(InvalidResourceException::class);
        $image = new Image('some path');
    }

    /**
     * @test
     */
    public function convertImageSizeByUnitConverter(): void
    {
        $converter = $this->getMockBuilder(UnitConverter::class)
                          ->getMock();

        $size            = 100;
        $sampleImageSize = 315;

        $converter->expects($this->exactly(2))
                  ->method('convertUnit')
                  ->withConsecutive([$sampleImageSize, UnitConverter::UNIT_PIXEL], [$sampleImageSize, UnitConverter::UNIT_PIXEL])
                  ->willReturnOnConsecutiveCalls($this->returnValue($size), $this->returnValue($size));


        $image = new Image(TEST_RESOURCES_DIR.'/domek.png', $converter);

        $this->assertEquals($size, $image->getOriginalWidth());
        $this->assertEquals($size, $image->getOriginalHeight());
    }
}
