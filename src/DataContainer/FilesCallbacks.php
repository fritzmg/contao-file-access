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
use Contao\FilesModel;
use Contao\Input;

class FilesCallbacks
{
    /**
     * @Callback(table="tl_files", target="config.onload")
     */
    public function onLoadCallback(DataContainer $dc): void
    {
        if ('editAll' === Input::get('act') || (null !== ($filesModel = FilesModel::findOneByPath($dc->id)) && 'folder' === $filesModel->type)) {
            PaletteManipulator::create()
                ->addField('groups', null)
                ->applyToPalette('default', 'tl_files')
            ;
        }
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
}
