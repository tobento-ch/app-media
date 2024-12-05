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

namespace Tobento\App\Media\Test\App\Migration;

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\DirCopy;
use Tobento\Service\Migration\Action\DirDelete;
use Tobento\Service\Dir\DirsInterface;

class MediaFiles implements MigrationInterface
{
    public function __construct(
        protected DirsInterface $dirs,
    ) {}
    
    public function description(): string
    {
        return 'Test files';
    }

    public function install(): ActionsInterface
    {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        return new Actions(
            new DirCopy(
                dir: $resources.'icons/',
                destDir: $this->dirs->get('views').'icons/',
                name: 'Media icons',
                type: 'views',
                description: 'Media icons.',
            ),
            new DirCopy(
                dir: $resources.'picture-definitions/',
                destDir: $this->dirs->get('views').'picture-definitions/',
                name: 'Media picture definitions',
                type: 'views',
                description: 'Media picture definitions.',
            ),
            new DirCopy(
                dir: $resources.'uploads/',
                destDir: $this->dirs->get('app').'storage/uploads/',
                name: 'Media upload files',
                type: 'uploads',
                description: 'Media upload files.',
            ),
        );
    }

    public function uninstall(): ActionsInterface
    {
        return new Actions();
    }
}