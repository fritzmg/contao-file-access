<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFileAccessBundle\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\Input;
use Webmozart\PathUtil\Path;

class FilesCallbacks
{
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @Callback(table="tl_files", target="config.onload")
     */
    public function onLoadCallback(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        if ('editAll' === Input::get('act')) {
            $this->adjustPalette();

            return;
        }

        $filesModel = FilesModel::findOneByPath($dc->id);

        if (null === $filesModel && is_dir(Path::join($this->projectDir, $dc->id))) {
            $filesModel = Dbafs::addResource($dc->id);
        }

        if (null === $filesModel || 'folder' !== $filesModel->type) {
            return;
        }

        $this->adjustPalette();
    }

    /**
     * @Callback(table="tl_files", target="fields.groups.save")
     */
    public function onSaveCallback($value, DataContainer $dc)
    {
        if ($dc->activeRecord instanceof FilesModel && 'folder' !== $dc->activeRecord->type) {
            return null;
        }

        return $value;
    }

    private function adjustPalette(): void
    {
        PaletteManipulator::create()
            // We have to use a non-existent legend here (see https://github.com/contao/contao/pull/5032)
            ->addField('groups', 'foobar')
            ->addField('protectResizedImages', 'foobar')
            ->applyToPalette('default', 'tl_files')
        ;
    }
}
