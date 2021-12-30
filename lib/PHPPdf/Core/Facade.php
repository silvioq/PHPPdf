<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Util\AbstractStringFilterContainer;
use PHPPdf\Util\StringFilter;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\Parser\ColorPaletteParser;
use PHPPdf\Parser\Parser;
use PHPPdf\Core\Parser\StylesheetConstraint;
use PHPPdf\Core\Parser\CachingStylesheetConstraint;
use PHPPdf\Core\Parser\DocumentParser;
use PHPPdf\Core\Configuration\Loader;
use PHPPdf\Core\Node\TextTransformator;
use PHPPdf\Core\Parser\StylesheetParser;
use PHPPdf\Core\Parser\ComplexAttributeFactoryParser;
use PHPPdf\Core\Parser\FontRegistryParser;
use PHPPdf\Cache\Cache;
use PHPPdf\Cache\NullCache;
use PHPPdf\DataSource\DataSource;
use PHPPdf\Core\Parser\NodeFactoryParser;

/**
 * Simple facade whom encapsulate logical complexity of this library
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Facade extends AbstractStringFilterContainer
{
    private DocumentParser   $documentParser;
    private StylesheetParser $stylesheetParser;
    private Document         $document;
    private Cache            $cache;
    private bool             $loaded                          = false;
    private bool             $useCacheForStylesheetConstraint = false;
    private Loader           $configurationLoader;
    private ?Parser          $colorPaletteParser              = null;
    private string           $engineType                      = 'pdf';

    public function __construct(Loader $configurationLoader, Document $document, DocumentParser $documentParser, StylesheetParser $stylesheetParser)
    {
        $this->configurationLoader = $configurationLoader;
        $this->configurationLoader->setUnitConverter($document);

        $this->setCache(NullCache::getInstance());
        $documentParser->setDocument($document);
        $nodeManager = $documentParser->getNodeManager();
        if ($nodeManager) {
            $documentParser->addListener($nodeManager);
        }
        $this->setDocumentParser($documentParser);
        $this->setStylesheetParser($stylesheetParser);
        $this->setDocument($document);
    }

    public function setEngineType($engineType): void
    {
        $this->engineType = $engineType;
    }

    public function setCache(Cache $cache): void
    {
        $this->cache = $cache;
    }

    public function getDocumentParser(): DocumentParser
    {
        return $this->documentParser;
    }

    public function getStylesheetParser(): StylesheetParser
    {
        return $this->stylesheetParser;
    }

    public function setDocumentParser(DocumentParser $documentParser): void
    {
        $this->documentParser = $documentParser;
    }

    public function setStylesheetParser(StylesheetParser $stylesheetParser): void
    {
        $this->stylesheetParser = $stylesheetParser;
    }

    public function setColorPaletteParser(Parser $colorPaletteParser): void
    {
        $this->colorPaletteParser = $colorPaletteParser;
    }

    protected function getColorPaletteParser(): ColorPaletteParser|Parser
    {
        if ($this->colorPaletteParser === null) {
            $this->colorPaletteParser = new ColorPaletteParser();
        }

        return $this->colorPaletteParser;
    }

    /**
     * Returns pdf document object
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    private function setDocument(Document $document): void
    {
        $this->document = $document;
    }

    private function setFacadeConfiguration(FacadeConfiguration $facadeConfiguration)
    {
        $this->facadeConfiguration = $facadeConfiguration;
    }

    /**
     * @param boolean $useCache Stylsheet constraints should be cached?
     */
    public function setUseCacheForStylesheetConstraint(bool $useCache): void
    {
        $this->useCacheForStylesheetConstraint = $useCache;
    }

    /**
     * Convert text document to pdf document
     *
     * @param string|DataSource                       $documentContent     Source document content
     * @param string|DataSource|DataSource[]|string[] $stylesheetContents  Stylesheet source(s)
     * @param string|DataSource|null                  $colorPaletteContent Palette of colors source
     *
     * @return string|null Content of pdf document
     *
     * @throws \PHPPdf\Exception\Exception
     */
    public function render(DataSource|string $documentContent, array|string|DataSource|null $stylesheetContents = [], string|DataSource $colorPaletteContent = null): ?string
    {
        $colorPalette = new ColorPalette((array) $this->configurationLoader->createColorPalette());

        if ($colorPaletteContent) {
            $colorPalette->merge($this->parseColorPalette($colorPaletteContent));
        }

        $this->document->setColorPalette($colorPalette);

        $complexAttributeFactory = $this->configurationLoader->createComplexAttributeFactory();

        $this->getDocument()->setComplexAttributeFactory($complexAttributeFactory);
        $fontDefinitions = $this->configurationLoader->createFontRegistry($this->engineType);
        $this->getDocument()->addFontDefinitions($fontDefinitions);
        $this->getDocumentParser()->setComplexAttributeFactory($complexAttributeFactory);
        $this->getDocumentParser()->setNodeFactory($this->configurationLoader->createNodeFactory());

        $stylesheetConstraint = $this->retrieveStylesheetConstraint($stylesheetContents);

        foreach ($this->stringFilters as $filter) {
            $documentContent = $filter->filter($documentContent);
        }

        $pageCollection = $this->getDocumentParser()->parse($documentContent, $stylesheetConstraint);
        $this->updateStylesheetConstraintCacheIfNecessary($stylesheetConstraint);
        unset($stylesheetConstraint);

        return $this->doRender($pageCollection);
    }

    private function parseColorPalette(DataSource|string $colorPaletteContent): array
    {
        if (!$colorPaletteContent instanceof DataSource) {
            $colorPaletteContent = DataSource::fromString($colorPaletteContent);
        }

        $id = $colorPaletteContent->getId();

        if ($this->cache->test($id)) {
            $colors = (array) $this->cache->load($id);
        } else {
            $colors = (array) $this->getColorPaletteParser()->parse($colorPaletteContent->read());
            $this->cache->save($colors, $id);
        }

        return $colors;
    }

    private function doRender($pageCollection): ?string
    {
        $this->getDocument()->draw($pageCollection);
        $pageCollection->flush();
        unset($pageCollection);

        $content = $this->getDocument()->render();
        $this->getDocument()->initialize();

        return $content;
    }

    public function retrieveStylesheetConstraint($stylesheetContents)
    {
        if ($stylesheetContents === null) {
            return null;
        } elseif (is_string($stylesheetContents)) {
            $stylesheetContents = [DataSource::fromString($stylesheetContents)];
        } elseif ($stylesheetContents instanceof DataSource) {
            $stylesheetContents = [$stylesheetContents];
        } elseif (!is_array($stylesheetContents)) {
            throw new InvalidArgumentException('$stylesheetContents must be an array, null or DataSource object.');
        }

        $constraints = [];

        foreach ($stylesheetContents as $stylesheetContent) {
            if (!$stylesheetContent instanceof DataSource) {
                $stylesheetContent = DataSource::fromString($stylesheetContent);
            }

            if (!$this->useCacheForStylesheetConstraint) {
                $constraints[] = $this->parseStylesheet($stylesheetContent);
            } else {
                $constraints[] = $this->loadStylesheetConstraintFromCache($stylesheetContent);
            }
        }

        if (!$constraints) {
            return null;
        } elseif (count($constraints) === 1) {
            return current($constraints);
        }

        return $constraints[0]->merge($constraints);
    }

    private function parseStylesheet(DataSource $ds): StylesheetConstraint
    {
        return $this->getStylesheetParser()->parse($ds->read());
    }

    private function loadStylesheetConstraintFromCache(DataSource $ds)
    {
        $id = $ds->getId();
        if ($this->cache->test($id)) {
            $stylesheetConstraint = $this->cache->load($id);
        } else {
            $csc = new CachingStylesheetConstraint();
            $csc->setCacheId($id);
            $this->getStylesheetParser()->setRoot($csc);

            $stylesheetConstraint = $this->parseStylesheet($ds);
            $this->cache->save($stylesheetConstraint, $id);
        }

        return $stylesheetConstraint;
    }

    private function updateStylesheetConstraintCacheIfNecessary(StylesheetConstraint $constraint = null): void
    {
        if ($constraint && $this->useCacheForStylesheetConstraint && $constraint->isResultMapModified()) {
            $this->cache->save($constraint, $constraint->getCacheId());
        }
    }
}
