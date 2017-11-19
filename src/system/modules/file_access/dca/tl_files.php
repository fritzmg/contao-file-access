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
	'save_callback'           => array(array('tl_files_file_access', 'protectFolder')),
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


/**
 * Helper class
 */
class tl_files_file_access
{
	/**
	 * Return a checkbox to delete session data
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function protectFolder($varValue, DataContainer $dc)
	{
		$strPath = $dc->id;

		// Check whether the temporary name has been replaced already (see #6432)
		if (\Input::post('name'))
		{
			if (\Validator::isInsecurePath(\Input::post('name')))
			{
				throw new \RuntimeException('Invalid file or folder name ' . \Input::post('name'));
			}

			$count = 0;
			$strName = basename($strPath);

			if (($strNewPath = str_replace($strName, \Input::post('name'), $strPath, $count)) && $count > 0 && is_dir(TL_ROOT . '/' . $strNewPath))
			{
				$strPath = $strNewPath;
			}
		}

		// Only show for folders (see #5660)
		if (!is_dir(TL_ROOT . '/' . $strPath))
		{
			return '';
		}

		// Protect or unprotect the folder
		if ($varValue)
		{
			\File::putContent($strPath . '/.htaccess', '');
		}
		else
		{
			@unlink($strPath . '/.htaccess');
		}

		return $varValue;
	}
}
