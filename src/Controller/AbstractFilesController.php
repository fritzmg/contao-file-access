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
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Date;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

abstract class AbstractFilesController implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    /**
     * @throws PageNotFoundException
     * @throws AccessDeniedException
     * @throws InsufficientAuthenticationException
     */
    protected function checkFilePermissions(FilesModel $filesModel): void
    {
        // Check folder permissions
        $allowLogin = false;
        $allowAccess = false;

        // Get the current user
        $user = $this->tokenStorage()->getToken()?->getUser();

        // Check if the current user can access their home directory
        $canAccessHomeDir = $user instanceof FrontendUser && !empty($user->homeDir) && $user->accessHomeDir;

        do {
            // Check if this is a folder
            if ('folder' === $filesModel->type) {
                // Check if the current directory is an accessible user home
                $isHomeDir = (bool) $this->connection()->fetchOne('SELECT COUNT(*) FROM tl_member WHERE accessHomeDir = 1 AND homeDir = ?', [$filesModel->uuid]);

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
            if ($this->authChecker()->isGranted('ROLE_MEMBER')) {
                throw new AccessDeniedException();
            }

            // Otherwise throw 401 exception
            throw new InsufficientAuthenticationException();
        }
    }

    protected function setRootPage(Request $request): void
    {
        $root = $this->findFirstPublishedRootByHostAndLanguage($request->getHost(), $request->getLocale());

        if (null !== $root) {
            $root->loadDetails();
            $request->attributes->set('pageModel', $root);
            $GLOBALS['objPage'] = $root;
        }
    }

    #[SubscribedService]
    protected function connection(): Connection
    {
        return $this->container->get(__METHOD__);
    }

    #[SubscribedService]
    protected function tokenStorage(): TokenStorageInterface
    {
        return $this->container->get(__METHOD__);
    }

    #[SubscribedService]
    protected function authChecker(): AuthorizationCheckerInterface
    {
        return $this->container->get(__METHOD__);
    }

    #[SubscribedService]
    protected function tokenChecker(): TokenChecker
    {
        return $this->container->get(__METHOD__);
    }

    private function findFirstPublishedRootByHostAndLanguage(string $host, string $language): ?PageModel
    {
        $t = PageModel::getTable();
        $columns = ["$t.type='root' AND ($t.dns=? OR $t.dns='') AND ($t.language=? OR $t.fallback='1')"];
        $values = [$host, $language];
        $options = ['order' => "$t.dns DESC, $t.fallback"];

        if (!$this->tokenChecker()->isPreviewMode()) {
            $time = Date::floorToMinute();
            $columns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        return PageModel::findOneBy($columns, $values, $options);
    }
}
