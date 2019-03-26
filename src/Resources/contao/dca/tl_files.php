<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_files']['config']['onload_callback'][] = [\InspiredMinds\ContaoFileAccessBundle\DataContainer\FilesCallbacks::class, 'onLoadCallback'];

$GLOBALS['TL_DCA']['tl_files']['fields']['protected']['input_field_callback'] = [\InspiredMinds\ContaoFileAccessBundle\DataContainer\FilesCallbacks::class, 'onProtectedInputFieldCallback'];
$GLOBALS['TL_DCA']['tl_files']['fields']['protected']['sql'] = "char(1) NOT NULL default ''";
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
