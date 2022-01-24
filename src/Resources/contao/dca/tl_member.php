<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_member']['fields']['homeDir']['eval']['tl_class'] = 'clr w50';

$GLOBALS['TL_DCA']['tl_member']['fields']['accessHomeDir'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 cbx m12'],
    'sql'       => ['type' => 'boolean', 'default' => false],
];

PaletteManipulator::create()
    ->addField('accessHomeDir', 'homeDir')
    ->applyToSubpalette('assignDir', 'tl_member');
