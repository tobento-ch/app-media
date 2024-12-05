<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Media\Migration;

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\DirCopy;
use Tobento\Service\Migration\Action\DirDelete;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Dir\DirsInterface;

/**
 * MediaExtended migration.
 */
class MediaExtended implements MigrationInterface
{
    /**
     * @var array The translation files.
     */
    protected array $transFiles;
    
    /**
     * Create a new Media instance.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        $this->transFiles = [
            $this->dirs->get('trans').'en/' => [
                $resources.'trans/en/en-media.json',
                $resources.'trans/en/cropper.json',
            ],
            $this->dirs->get('trans').'de/' => [
                $resources.'trans/de/de-media.json',
                $resources.'trans/de/cropper.json',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Media views, assets and translation files.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        return new Actions(
            new FilesCopy(
                files: $this->transFiles,
                type: 'trans',
                description: 'Media translation files.',
            ),
            new DirCopy(
                dir: $resources.'views/media/',
                destDir: $this->dirs->get('views').'media/',
                name: 'Media views',
                type: 'views',
                description: 'Media views.',
            ),
            new DirCopy(
                dir: $resources.'assets/media/',
                destDir: $this->dirs->get('public').'assets/media/',
                name: 'Media assets',
                type: 'assets',
                description: 'Media assets.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            new FilesDelete(
                files: $this->transFiles,
                type: 'trans',
                description: 'Media translation files.',
            ),
            new DirDelete(
                dir: $this->dirs->get('views').'media/',
                name: 'Media views',
                type: 'views',
                description: 'Media views.',
            ),
            new DirDelete(
                dir: $this->dirs->get('public').'assets/media/',
                name: 'Media assets',
                type: 'assets',
                description: 'Media assets.',
            ),
        );
    }
}