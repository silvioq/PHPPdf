<?php

declare(strict_types=1);

namespace PHPPdf\Test\Examples;

use GlobIterator;
use PHPPdf\Core\Facade;
use PHPPdf\Core\FacadeBuilder;
use PHPPdf\DataSource\DataSource;
use PHPUnit\Framework\TestCase;

class ExampleGeneratorTest extends TestCase
{
    private const EXAMPLES_DIR = __DIR__.'/../../../../examples/';
    private const OUTPUT_DIR   = __DIR__.'/../../../../testExampleOutput/';

    private Facade $facade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->facade = FacadeBuilder::create()->setEngineType('pdf')->build();
        if(!file_exists(self::OUTPUT_DIR)){
            mkdir(self::OUTPUT_DIR);
        }
    }

    /**
     * @dataProvider examplesProvider
     */
    public function testExamples($exampleToTest): void
    {
//        $this->markTestIncomplete('Comparing images is not very effective, use test at own risk');

        $documentFilename   = self::EXAMPLES_DIR.$exampleToTest.'.xml';
        $stylesheetFilename = self::EXAMPLES_DIR.$exampleToTest.'-style.xml';

        $xml           = str_replace('dir:', self::EXAMPLES_DIR, file_get_contents($documentFilename));
        $stylesheetXml = is_readable($stylesheetFilename) ? str_replace('dir:', self::EXAMPLES_DIR, file_get_contents($stylesheetFilename)) : null;
        $stylesheet    = $stylesheetXml ? DataSource::fromString($stylesheetXml) : null;

        $content = $this->facade->render($xml, $stylesheet);
        $assertedImagick = new \Imagick();
        $assertedImagick->setResolution(50, 50);
        $assertedImagick->readImageBlob($content);
        $assertedImagick->resetIterator();

        $assertedImagick = $assertedImagick->appendImages(true);

        $testImagick = new \Imagick();
        $testImagick->setResolution(50, 50);
        $testImagick->readImageBlob(file_get_contents(self::EXAMPLES_DIR.'generated/'.$exampleToTest.'.pdf'));
        $testImagick->resetIterator();

        $testImagick = $testImagick->appendImages(true);
        $diff        = $assertedImagick->compareImages($testImagick, 1);

        if ($diff[1] !== 0.0) {
            file_put_contents(self::OUTPUT_DIR.$exampleToTest.'.pdf', $content);
            $assertedImagick->writeImage(self::OUTPUT_DIR.$exampleToTest.'-generated.jpg');
            $testImagick->writeImage(self::OUTPUT_DIR.$exampleToTest.'-original.jpg');
            $diff[0]->writeImage(self::OUTPUT_DIR.$exampleToTest.'-diff.jpg');
            file_put_contents(self::OUTPUT_DIR.$exampleToTest.'.pdf', $content);
        }

        $this->assertSame(0.0, $diff[1]);
    }

    public function examplesProvider(): array
    {
        $examples = [];

        $iter = new GlobIterator(self::EXAMPLES_DIR.'*.xml');
        foreach ($iter as $file) {
            $name = $file->getBasename('.xml');
            if (!str_contains($name, '-style')) {
                $examples[] = [$name];
            }
        }

        return $examples;
    }
}
