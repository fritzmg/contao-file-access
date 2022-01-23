<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_member']['subpalettes']['assignDir'] = 'homeDir,accessHomeDir';

$GLOBALS['TL_DCA']['tl_member']['fields']['homeDir']['eval']['tl_class'] = 'clr w50';

$GLOBALS['TL_DCA']['tl_member']['fields']['accessHomeDir'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 cbx m12'],
    'sql'       => "char(1) NOT NULL default ''"
];
