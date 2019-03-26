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

        // Check whether the file exists
        if (!is_file($this->rootDir.'/'.$file)) {
            throw new PageNotFoundException();
        }

        // Initialize the Contao framework
        $this->framework->initialize();

        // Authenticate the user
        FrontendUser::getInstance()->authenticate();

        // Check access protection
        if (null !== ($filesModel = FilesModel::findOneByPath($file))) {
            do {
                if (!Controller::isVisibleElement($filesModel)) {
                    throw new AccessDeniedException();
                }

                $filesModel = FilesModel::findById($filesModel->pid);
            } while (null !== $filesModel);
        }

        // Close the session
        $this->session->save();

        // Try to override max_execution_time
        @ini_set('max_execution_time', '0');

        // Return file to browser
        return new BinaryFileResponse($this->rootDir.'/'.$file);
    }
}
