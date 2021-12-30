<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

/**
 * Configuration for Facade. Contains information about config files.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FacadeConfiguration
{
    private array $configFiles;

    public function __construct()
    {
        $this->configFiles = [
            'node'              => __DIR__.'/../Resources/config/nodes.xml',
            'complex-attribute' => __DIR__.'/../Resources/config/complex-attributes.xml',
            'font'              => __DIR__.'/../Resources/config/fonts.xml',
        ];
    }

    /**
     * Static constructor
     *
     */
    public static function newInstance(): FacadeConfiguration
    {
        return new self();
    }

    /**
     * Set config file for populating node factory
     *
     * @return FacadeConfiguration
     */
    public function setNodesConfigFile(string $file): static
    {
        $this->configFiles['node'] = $file;

        return $this;
    }

    public function getNodesConfigFile(): string
    {
        return $this->configFiles['node'];
    }

    /**
     * Set config file for populating complex attribute factory
     *
     * @return FacadeConfiguration
     */
    public function setComplexAttributesConfigFile(string $file): static
    {
        $this->configFiles['complex-attribute'] = $file;

        return $this;
    }

    public function getComplexAttributesConfigFile(): string
    {
        return $this->configFiles['complex-attribute'];
    }

    /**
     * Set config file for populating font registry
     *
     * @return FacadeConfiguration
     */
    public function setFontsConfigFile(string $file): static
    {
        $this->configFiles['font'] = $file;

        return $this;
    }

    public function getFontsConfigFile(): string
    {
        return $this->configFiles['font'];
    }
}
