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


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'FileAccess',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'FileAccess\FileAccess' => 'system/modules/file_access/classes/FileAccess.php',
));
