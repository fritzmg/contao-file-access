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
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Date;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Security;
use Webmozart\PathUtil\Path;

class FilesController
{
    protected $rootDir;
    protected $session;
    protected $framework;
    protected $security;
    protected $db;
    protected $tokenChecker;

    public function __construct(string $rootDir, Session $session, ContaoFramework $framework, Security $security, Connection $db, TokenChecker $tokenChecker)
    {
        $this->rootDir = $rootDir;
        $this->session = $session;
        $this->framework = $framework;
        $this->security = $security;
        $this->db = $db;
        $this->tokenChecker = $tokenChecker;
    }

    public function fileAction(Request $request, string $file): BinaryFileResponse
    {
        $file = 'files/'.$file;

        // Make sure there are no attempts to hack the file system
        if (preg_match('@^\.+@i', $file) || preg_match('@\.+/@i', $file) || preg_match('@(://)+@i', $file)) {
            throw new PageNotFoundException();
        }

        // Initialize the Contao framework
        $this->framework->initialize(true);

        // Set the root page for the domain as the pageModel attribute
        $root = $this->findFirstPublishedRootByHostAndLanguage($request->getHost(), $request->getLocale());

        if (null !== $root) {
            $root->loadDetails();
            $request->attributes->set('pageModel', $root);
            $GLOBALS['objPage'] = $root;
        }

        // Check whether the file exists
        if (!is_file(Path::join($this->rootDir, $file))) {
            throw new PageNotFoundException();
        }

        // Get FilesModel entity
        $filesModel = FilesModel::findOneByPath($file);

        // Dynamically add the file to the DBAFS
        if (null === $filesModel) {
            $filesModel = Dbafs::addResource($file);
        }

        // Do not allow files that are not in the database or don't have a parent
        if (null === $filesModel || empty($filesModel->pid)) {
            throw new PageNotFoundException();
        }

        // Check folder permissions
        $allowLogin = false;
        $allowAccess = false;

        // Get the current user
        $user = $this->security->getUser();

        // Check if the current user can access their home directory
        $canAccessHomeDir = $user instanceof FrontendUser && !empty($user->homeDir) && $user->accessHomeDir;

        do {
            // Check if this is a folder
            if ('folder' === $filesModel->type) {
                // Check if the current directory is an accessible user home
                $isHomeDir = (bool) $this->db->fetchOne('SELECT COUNT(*) FROM tl_member WHERE accessHomeDir = 1 AND homeDir = ?', [$filesModel->uuid]);

                // Only check when member groups have been set or the folder is a user home
                if (null !== $filesModel->groups || $isHomeDir) {
                    $allowLogin = true;

                    // Set the model to protected on the fly
                    $filesModel->protected = true;

                    // Check if this is the user's home directory
                    $isUserHomeDir = $user instanceof FrontendUser && $user->homeDir === $filesModel->uuid;

                    // Check access
                    if (($canAccessHomeDir && $isUserHomeDir) || Controller::isVisibleElement($filesModel)) {
                        $allowAccess = true;
                        break;
                    }
                }
            }

            // Get the parent folder
            $filesModel = $filesModel->pid ? FilesModel::findById($filesModel->pid) : null;
        } while (null !== $filesModel);

        // Throw 404 exception, if there were no user homes or folders with member groups
        if (!$allowLogin) {
            throw new PageNotFoundException();
        }

        // Deny access
        if (!$allowAccess) {
            // If a user is authenticated or the 401 exception does not exist, throw 403 exception
            if ($this->security->isGranted('ROLE_MEMBER')) {
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
        return new BinaryFileResponse(Path::join($this->rootDir, $file));
    }

    protected function findFirstPublishedRootByHostAndLanguage(string $host, string $language): ?PageModel
    {
        $columns = ["type='root' AND (dns=? OR dns='') AND (language=? OR fallback='1')"];
        $values = [$host, $language];
        $options = ['order' => 'dns DESC, fallback'];

        if (!$this->tokenChecker->isPreviewMode()) {
            $time = Date::floorToMinute();
            $columns[] = "published='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'$time')";
        }

        return PageModel::findOneBy($columns, $values, $options);
    }
}
