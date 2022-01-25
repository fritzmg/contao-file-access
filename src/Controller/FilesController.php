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
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Security;

class FilesController
{
    protected $rootDir;
    protected $session;
    protected $framework;
    private $security;

    public function __construct(string $rootDir, Session $session, ContaoFramework $framework, Security $security)
    {
        $this->rootDir = $rootDir;
        $this->session = $session;
        $this->framework = $framework;
        $this->security = $security;
    }

    public function fileAction(Request $request, string $file): BinaryFileResponse
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

        // Dynamically add the file to the DBAFS
        if (null === $filesModel) {
            $filesModel = Dbafs::addResource($file);
        }

        // Do not allow files that are not in the database or don't have a parent
        if (null === $filesModel || null === $filesModel->pid) {
            throw new PageNotFoundException();
        }

        // Check folder permissions
        $allowLogin = false;
        $allowAccess = false;

        // Get the current user
        $user = $this->security->getUser();

        if (false === $user instanceof FrontendUser) {
            throw new PageNotFoundException();
        }

        // Check if the current user can access their home directory
        $canAccessHomeDir = !empty($user->homeDir) && $user->accessHomeDir;

        do {
            // Only check for folders and when member groups have been set
            // or access to member home directory
            if ('folder' === $filesModel->type && (null !== $filesModel->groups || $canAccessHomeDir)) {
                $allowLogin = true;

                // Set the model to protected on the fly
                $filesModel->protected = true;

                // Check if this file is the home directory
                $isHomeDir = $user->homeDir === $filesModel->uuid;

                // Check access
                if (($canAccessHomeDir && $isHomeDir) || (Controller::isVisibleElement($filesModel))) {
                    $allowAccess = true;
                    break;
                }
            }

            // Get the parent folder
            $filesModel = FilesModel::findById($filesModel->pid);
        } while (null !== $filesModel);

        // Throw 404 exception, if there were no folders with member groups
        if (!$allowLogin) {
            throw new PageNotFoundException();
        }

        // Deny access
        if (!$allowAccess) {
            // Set the root page for the domain as the pageModel attribute
            $root = PageModel::findFirstPublishedRootByHostAndLanguage($request->getHost(), $request->getLocale());

            if (null !== $root) {
                $request->attributes->set('pageModel', $root);
            }

            // If a user is authenticated or the 401 exception does not exist, throw 403 exception
            if ($authenticated || !class_exists(InsufficientAuthenticationException::class)) {
                throw new AccessDeniedException();
            }

            // Otherwise throw 401 exception
            throw new InsufficientAuthenticationException();
        }

        // Close the session
        $this->session->save();

        // Try to override max_execution_time
        @ini_set('max_execution_time', '0');

        // Return file to browser
        return new BinaryFileResponse($this->rootDir.'/'.$file);
    }
}
