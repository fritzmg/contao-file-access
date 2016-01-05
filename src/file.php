<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package   file_access
 * @author    Fritz Michael Gschwantner <https://github.com/fritzmg>
 * @license   LGPL-3.0+
 * @copyright Fritz Michael Gschwantner 2016
 */

// Set the script name
define('TL_SCRIPT', 'file.php');

// Initialize the system
define('TL_MODE', 'FE');
require __DIR__ . '/system/initialize.php';

// Run the controller
$controller = new FileAccess;
$controller->run();
