<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine\Imagine;

use Imagine\Exception\RuntimeException;
use PHPPdf\Core\Engine\Imagine\Image;
use Imagine\Gd\Imagine;
use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @test
     */
    public function createImageObject(): void
    {
        try {
            $imagine = new Imagine();
            $image   = new Image(TEST_RESOURCES_DIR.'/domek.png', $imagine);

            $imagineImage = $image->getWrappedImage();

            $this->assertEquals($imagineImage->getSize()->getHeight(), $image->getOriginalHeight());
            $this->assertEquals($imagineImage->getSize()->getWidth(), $image->getOriginalWidth());
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Gd not installed') {
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    /**
     * @test
     *
     */
    public function throwExceptionOnUnexistedImage(): void
    {
        $this->expectException(InvalidResourceException::class);
        try {
            $imagine = new Imagine();
            $image   = new Image('some path', $imagine);

            $image->getWrappedImage();

        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Gd not installed') {
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
