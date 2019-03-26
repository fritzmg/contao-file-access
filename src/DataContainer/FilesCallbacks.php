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
use Contao\Input;

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

    public function onProtectedInputFieldCallback(DataContainer $dc): string
    {
        if ('tl_files' === Input::post('FORM_SUBMIT')) {
            /** @var FilesModel $filesModel */
            if (null !== ($filesModel = FilesModel::findById($dc->activeRecord->id))) {
                $filesModel->protected = Input::post($dc->inputName) ? '' : '1';
                $filesModel->save();
            }
        }

        return (new \tl_files())->protectFolder($dc);
    }
}
