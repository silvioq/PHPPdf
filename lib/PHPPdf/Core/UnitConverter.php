<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

/**
 * Unit converter
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface UnitConverter
{
    //unit of x and y axes is 1/72 inch
    public const UNITS_PER_INCH = 72;
    public const MM_PER_INCH    = 25.3995;
    
    //the same as point (pt)
    public const  UNIT_PDF = 'pu';
    //the same as pdf unit (pu)
    public const  UNIT_POINT = 'pt';

    public const  UNIT_PIXEL = 'px';
    public const  UNIT_CENTIMETER = 'cm';
    public const  UNIT_MILIMETER = 'mm';
    public const  UNIT_INCH = 'in';
    public const  UNIT_PICA = 'pc';
    public const  UNIT_EM = 'em';
    public const  UNIT_EX = 'ex';
    
    public function convertUnit($value, $unit = null);
    public function convertPercentageValue($percent, $value);
}
