<?php

declare(strict_types=1);

/*
 * This file is part of the Contao File Access extension.
 *
 * (c) INSPIRED MINDS
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFileAccessBundle\Controller;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Dbafs;
use Contao\FilesModel;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class FilesController extends AbstractFilesController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly string $projectDir,
    ) {
    }

    public function fileAction(Request $request, string $file): BinaryFileResponse
    {
        $file = 'files/'.$file;

        // Make sure there are no attempts to hack the file system
        if (preg_match('@^\.+@i', $file) || preg_match('@\.+/@i', $file) || preg_match('@(://)+@i', $file)) {
            throw new PageNotFoundException();
        }

        // Initialize the Contao framework
        $this->framework->initialize();

        // Set the root page for the domain as the pageModel attribute
        $this->setRootPage($request);

        // Check whether the file exists
        if (!is_file(Path::join($this->projectDir, $file))) {
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

        // Check the permissions
        $this->checkFilePermissions($filesModel);

        // Close the session
        $request->getSession()->save();

        // Try to override max_execution_time
        @ini_set('max_execution_time', '0');

        // Return file to browser
        return new BinaryFileResponse(Path::join($this->projectDir, $file));
    }
}
