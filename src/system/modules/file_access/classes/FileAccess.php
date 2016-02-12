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


namespace FileAccess;


/**
 * File access controller.
 *
 * @author Fritz Michael Gschwantner <https://github.com/fritzmg>
 */
class FileAccess extends \Frontend
{

	/**
	 * Initialize the object
	 */
	public function __construct()
	{
		// Load the user object before calling the parent constructor
		\FrontendUser::getInstance();
		parent::__construct();

		// Check whether a user is logged in
		define('BE_USER_LOGGED_IN', $this->getLoginStatus('BE_USER_AUTH'));
		define('FE_USER_LOGGED_IN', $this->getLoginStatus('FE_USER_AUTH'));

		// No back end user logged in
		if (!$_SESSION['DISABLE_CACHE'])
		{
			// Maintenance mode (see #4561 and #6353)
			if (\Config::get('maintenanceMode'))
			{
				header('HTTP/1.1 503 Service Unavailable');
				die_nicely('be_unavailable', 'This site is currently down for maintenance. Please come back later.');
			}

			// Disable the debug mode (see #6450)
			\Config::set('debugMode', false);
		}
	}


	/**
	 * Run the controller
	 */
	public function run()
	{
		$strFile = \Input::get('file', true);

		if ($strFile != '')
		{
			// Make sure there are no attempts to hack the file system
			if (preg_match('@^\.+@i', $strFile) || preg_match('@\.+/@i', $strFile) || preg_match('@(://)+@i', $strFile))
			{
				header('HTTP/1.1 404 Not Found');
				die('Invalid file name');
			}

			// Limit downloads to the files directory
			if (!preg_match('@^' . preg_quote(\Config::get('uploadPath'), '@') . '@i', $strFile))
			{
				header('HTTP/1.1 404 Not Found');
				die('Invalid path');
			}

			// Check whether the file exists
			if (!file_exists(TL_ROOT . '/' . $strFile))
			{
				header('HTTP/1.1 404 Not Found');
				die('File not found');
			}

			// find the path in the database
			if( ( $objFile = \FilesModel::findOneByPath( $strFile ) ) !== null )
			{
				// authenticate the frontend user
				\FrontendUser::getInstance()->authenticate();

				// check if file is protected
				if( !\Controller::isVisibleElement( $objFile ) )
				{
					$objHandler = new $GLOBALS['TL_PTY']['error_403']();
					$objHandler->generate($strFile);
				}
				elseif( $objFile->pid )
				{
					// check if parent folders are proteced
					do {
						$objFile = \FilesModel::findById( $objFile->pid );

						if( !\Controller::isVisibleElement( $objFile ) )
						{
							$objHandler = new $GLOBALS['TL_PTY']['error_403']();
							$objHandler->generate($strFile);
						}
					}
					while( $objFile->pid );
				}			
			}

			// get the file
			$objFile = new \File( $strFile );

			// Make sure no output buffer is active
			// @see http://ch2.php.net/manual/en/function.fpassthru.php#74080
			while (@ob_end_clean());

			// Prevent session locking (see #2804)
			session_write_close();

			// Disable zlib.output_compression (see #6717)
			@ini_set('zlib.output_compression', 'Off');

			// Set headers
			header('Content-Type: ' . $objFile->mime);
			header('Content-Length: ' . $objFile->filesize);

			// Disable maximum execution time
			@ini_set('max_execution_time', 0);

			// Output the file
			readfile( TL_ROOT . '/' . $objFile->path );
		}

		// Stop the script (see #4565)
		exit;
	}
}
