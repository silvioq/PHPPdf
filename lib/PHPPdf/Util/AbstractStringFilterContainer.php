<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Util;

/**
 * Abstract string filter container
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class AbstractStringFilterContainer implements StringFilterContainer
{
    protected array $stringFilters = [];

    public function setStringFilters(array $filters)
    {
        $this->stringFilters = [];

        foreach ($filters as $filter) {
            $this->addStringFilter($filter);
        }
    }

    protected function addStringFilter(StringFilter $filter)
    {
        $this->stringFilters[] = $filter;
    }
}
