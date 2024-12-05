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

namespace Tobento\App\Media\Picture;

use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\PictureTagInterface;

/**
 * PictureGeneratorInterface
 */
interface PictureGeneratorInterface
{
    /**
     * Returns the picture repository.
     *
     * @return PictureRepositoryInterface
     */
    public function pictureRepository(): PictureRepositoryInterface;
    
    /**
     * Generate a new picture from the given path, resource and definition.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition A named definition or definition instance.
     * @param bool $queue
     * @return PictureTagInterface
     */
    public function generate(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $queue = true,
    ): PictureTagInterface;
    
    /**
     * Regenerate a new picture from the given path, resource and definition.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition A named definition or definition instance.
     * @param bool $queue
     * @return PictureTagInterface
     */
    public function regenerate(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $queue = true,
    ): PictureTagInterface;
}