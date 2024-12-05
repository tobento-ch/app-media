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
 
namespace Tobento\App\Media\Queue;

use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;

/**
 * PictureQueueHandlerInterface
 */
interface PictureQueueHandlerInterface
{
    /**
     * Handle the picture.
     *
     * @param string $path
     * @param string|ResourceInterface $resource
     * @param string|DefinitionInterface $definition
     * @param bool $regenerate
     * @param bool $uniqueJob
     * @return void
     */
    public function handle(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $regenerate = false,
        bool $uniqueJob = true,
    ): void;
}