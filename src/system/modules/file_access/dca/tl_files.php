<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package   file_access
 * @author    Fritz Michael Gschwantner <https://github.com/fritzmg>
 * @license   LGPL-3.0+
 * @copyright Fritz Michael Gschwantner 2015
 */


$GLOBALS['TL_DCA']['tl_files']['palettes'] = str_replace( ';meta', ',groups;meta', $GLOBALS['TL_DCA']['tl_files']['palettes']);

$GLOBALS['TL_DCA']['tl_files']['fields']['protected'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_files']['protected'],
	'exclude'                 => true,
	'filter'                  => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'clr'),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_files']['fields']['groups'] = array
(
	'label'                   => $GLOBALS['TL_LANG']['tl_files']['groups'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_member_group.name',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL",
	'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
);
