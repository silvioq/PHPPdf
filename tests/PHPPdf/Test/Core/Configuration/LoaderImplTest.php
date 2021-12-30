<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Configuration;

use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory;
use PHPPdf\Core\Configuration\LoaderImpl;
use PHPPdf\Cache\NullCache;
use PHPPdf\Core\Node\NodeFactory;
use PHPPdf\PHPUnit\Framework\TestCase;

class LoaderImplTest extends TestCase
{
    public function saveCacheIfCacheIsEmpty($file, $loaderMethodName): void
    {
        $loader = new LoaderImpl();

        $nodeFile             = $this->getAttribute($loader, 'nodeFile');
        $complexAttributeFile = $this->getAttribute($loader, 'complexAttributeFile');
        $fontFile             = $this->getAttribute($loader, 'fontFile');
        $colorFile            = $this->getAttribute($loader, 'colorFile');

        $cache = $this->createMock(NullCache::class, ['test', 'save']);

        $cacheId = $this->invokeMethod($loader, 'getCacheId', [$$file]);

        $cache->expects($this->once())
              ->method('test')
              ->with($cacheId)
              ->willReturn(false);

        $cache->expects($this->once())
              ->method('save');

        $loader->setCache($cache);

        $this->invokeMethod($loader, $loaderMethodName);
    }

    public function configFileGetterProvider(): array
    {
        return [
            ['nodeFile', 'createNodeFactory', new NodeFactory()],
            ['complexAttributeFile', 'createComplexAttributeFactory', new ComplexAttributeFactory()],
            ['fontFiles', 'createFontRegistry', []],
            ['colorFile', 'createColorPalette', []],
        ];
    }

    /**
     * @test
     * @dataProvider configFileGetterProvider
     */
    public function loadCacheIfCacheIsntEmpty($file, $loaderMethodName, $cacheContent): void
    {
        $loader = new LoaderImpl();

        $nodeFile             = $this->getAttribute($loader, 'nodeFile');
        $complexAttributeFile = $this->getAttribute($loader, 'complexAttributeFile');
        $fontFiles            = $this->getAttribute($loader, 'fontFiles');
        $colorFile            = $this->getAttribute($loader, 'colorFile');

        $cache = $this->createPartialMock(NullCache::class, ['test', 'save', 'load']);

        $cacheId = $this->invokeMethod($loader, 'getCacheId', [is_array($$file) ? current($$file) : $$file]);

        $cache->expects($this->once())
              ->method('test')
              ->with($cacheId)
              ->willReturn(true);

        $cache->expects($this->once())
              ->method('load')
              ->with($cacheId)
              ->willReturn($cacheContent);

        $loader->setCache($cache);

        $this->invokeMethod($loader, $loaderMethodName);
    }
}
