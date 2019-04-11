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

use Contao\DataContainer;
use Contao\FilesModel;

class FilesCallbacks
{
    public function onLoadCallback(DataContainer $dc): void
    {
        /** @var FilesModel $filesModel */
        if (null !== ($filesModel = FilesModel::findOneByPath($dc->id)) && 'folder' === $filesModel->type) {
            \Contao\CoreBundle\DataContainer\PaletteManipulator::create()
                ->addField('groups', 'protected')
                ->applyToPalette('default', 'tl_files')
            ;
        }
    }
}
