<?php

declare(strict_types=1);

/*
 * This file is part of the Contao File Access extension.
 *
 * (c) INSPIRED MINDS
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_files']['fields']['protected']['eval']['tl_class'] = 'clr w50';

$GLOBALS['TL_DCA']['tl_files']['fields']['groups'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_files']['groups'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_member_group.name',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => 'blob NULL',
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
];
