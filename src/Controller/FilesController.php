<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFileAccessBundle\Controller;

use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class FilesController
{
    protected $rootDir;
    protected $session;
    protected $framework;

    public function __construct(string $rootDir, Session $session, ContaoFramework $framework)
    {
        $this->rootDir = $rootDir;
        $this->session = $session;
        $this->framework = $framework;
    }

    public function fileAction(string $file): BinaryFileResponse
    {
        $file = 'files/'.$file;

        // Make sure there are no attempts to hack the file system
        if (preg_match('@^\.+@i', $file) || preg_match('@\.+/@i', $file) || preg_match('@(://)+@i', $file)) {
            throw new PageNotFoundException();
        }

        // Check whether the file exists
        if (!is_file($this->rootDir.'/'.$file)) {
            throw new PageNotFoundException();
        }

        // Initialize the Contao framework
        $this->framework->initialize();

        // Authenticate the user
        $authenticated = FrontendUser::getInstance()->authenticate();

        // Required legacy constant
        \define('FE_USER_LOGGED_IN', $authenticated);

        // Get FilesModel entity
        $filesModel = FilesModel::findOneByPath($file);

        // Do not allow files that are not in the database or don't have a parent
        if (null === $filesModel || null === $filesModel->pid) {
            throw new PageNotFoundException();
        }

        // Check folder permissions
        do {
            if ('folder' === $filesModel->type && !Controller::isVisibleElement($filesModel)) {
                if ($authenticated || !class_exists(InsufficientAuthenticationException::class)) {
                    throw new AccessDeniedException();
                }

                throw new InsufficientAuthenticationException();
            }

            $filesModel = FilesModel::findById($filesModel->pid);
        } while (null !== $filesModel);

        // Close the session
        $this->session->save();

        // Try to override max_execution_time
        @ini_set('max_execution_time', '0');

        // Return file to browser
        return new BinaryFileResponse($this->rootDir.'/'.$file);
    }
}
