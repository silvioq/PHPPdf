<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Util;

/**
 * Resource path string filter
 * 
 * Replaces %resources% string to path to Resources directory
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ResourcePathStringFilter implements StringFilter
{
    private ?string $path = null;
    
    public function filter($value): array|string
    {
        return str_replace('%resources%', $this->getPathToResources(), $value);
    }
    
    private function getPathToResources(): array|string|null
    {
        if($this->path === null)
        {
            $this->path = str_replace('\\', '/', realpath(__DIR__.'/../Resources'));
        }
        
        return $this->path;
    }
}
