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

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredImageStorageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\Exception\FileNotExistsException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\PathUtil\Path;

class ProtectedImagesController extends AbstractFilesController
{
    private ImageFactoryInterface $imageFactory;
    private DeferredResizerInterface $resizer;
    private DeferredImageStorageInterface $deferredImageStorage;
    private Filesystem $filesystem;
    private ContaoFramework $framework;
    private ScopeMatcher $scopeMatcher;
    private string $targetDir;
    private string $projectDir;

    public function __construct(
        ImageFactoryInterface $imageFactory,
        DeferredResizerInterface $resizer,
        DeferredImageStorageInterface $deferredImageStorage,
        Filesystem $filesystem,
        ContaoFramework $framework,
        ScopeMatcher $scopeMatcher,
        string $targetDir,
        string $projectDir
    ) {
        $this->imageFactory = $imageFactory;
        $this->resizer = $resizer;
        $this->deferredImageStorage = $deferredImageStorage;
        $this->filesystem = $filesystem;
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->targetDir = $targetDir;
        $this->projectDir = $projectDir;
    }

    public function __invoke(Request $request, string $path): Response
    {
        try {
            $config = $this->deferredImageStorage->get($path);
        } catch (\Throwable $e) {
            throw new PageNotFoundException('Cannot retrieve deferred image information.', 0, $e);
        }

        $filePath = Path::join($this->targetDir, $config['path']);

        // Initialize the Contao framework
        $this->framework->initialize(true);

        // Set the root page for the domain as the pageModel attribute
        $this->setRootPage($request);

        // Check whether the file exists
        if (!is_file($filePath)) {
            throw new PageNotFoundException();
        }

        $relativeFilePath = Path::makeRelative($filePath, $this->projectDir);

        // Get FilesModel entity
        $filesModel = FilesModel::findOneByPath($relativeFilePath);

        // Dynamically add the file to the DBAFS
        if (null === $filesModel) {
            $filesModel = Dbafs::addResource($relativeFilePath);
        }

        // Do not allow files that are not in the database or don't have a parent
        if (null === $filesModel || empty($filesModel->pid)) {
            throw new PageNotFoundException();
        }

        // Check the permissions
        if (!$this->scopeMatcher->isBackendRequest($request)) {
            $this->checkFilePermissions($filesModel);
        }

        try {
            try {
                $image = $this->imageFactory->create(Path::join($this->targetDir, $path));
            } catch (\InvalidArgumentException $exception) {
                throw new PageNotFoundException($exception->getMessage(), 0, $exception);
            }

            if ($image instanceof DeferredImageInterface) {
                $this->resizer->resizeDeferredImage($image);

                // Re-save deferred image info (not ideal)
                $this->deferredImageStorage->set($path, $config);
            } elseif (!$this->filesystem->exists($image->getPath())) {
                throw new PageNotFoundException('Image does not exist');
            }
        } catch (FileNotExistsException $exception) {
            throw new PageNotFoundException($exception->getMessage(), 0, $exception);
        }

        // Close the session
        $request->getSession()->save();

        // Try to override max_execution_time
        @ini_set('max_execution_time', '0');

        return new BinaryFileResponse($image->getPath(), 200, ['Cache-Control' => 'private, max-age=31536000'], false);
    }
}
