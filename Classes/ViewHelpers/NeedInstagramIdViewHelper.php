<?php

namespace Pixelant\PxaSocialFeed\ViewHelpers;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class isNumericViewHelper
 * @package Pixelant\PxaSocialFeed\ViewHelpers
 */
class NeedInstagramIdViewHelper extends AbstractViewHelper
{

    /**
     * Register arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'string', 'Instagram id', true);
    }

    /**
     * check if value is numeric
     *
     * @return bool
     */
    public function render()
    {
        $value = $this->arguments['value'];

        return !is_numeric($value) && !GeneralUtility::isFirstPartOfStr($value, '#');
    }
}
