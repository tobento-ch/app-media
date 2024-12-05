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
 * Media migration.
 */
class Media implements MigrationInterface
{
    /**
     * @var array The config files.
     */
    protected array $configFiles;
    
    /**
     * Create a new Media instance.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        $this->configFiles = [
            $this->dirs->get('config') => [
                $resources.'config/media.php',
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
        return 'Media files.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        return new Actions(
            new FilesCopy(
                files: $this->configFiles,
                type: 'config',
                description: 'Media config file.',
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
                files: $this->configFiles,
                type: 'config',
                description: 'Media config file.',
            ),
        );
    }
}