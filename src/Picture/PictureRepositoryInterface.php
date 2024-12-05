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

use Tobento\App\Media\Exception\PictureException;
use Tobento\Service\Picture\CreatedPictureInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\PictureInterface;

/**
 * PictureRepositoryInterface
 */
interface PictureRepositoryInterface
{
    /**
     * Returns the found picture by the given path and definition.
     *
     * @param string $path
     * @param string|DefinitionInterface $definition
     * @return null|PictureInterface
     */
    public function findOne(string $path, string|DefinitionInterface $definition): null|PictureInterface;
    
    /**
     * Saves the created picture and return the saved.
     *
     * @param string $path
     * @param DefinitionInterface $definition
     * @param CreatedPictureInterface $picture
     * @return PictureInterface
     * @throws PictureException
     */
    public function save(
        string $path,
        DefinitionInterface $definition,
        CreatedPictureInterface $picture
    ): PictureInterface;
    
    /**
     * Deletes the created picture with all its created images.
     *
     * @param string $path
     * @param string|DefinitionInterface $definition
     * @return null|PictureInterface The deleted picture or null if none deleted.
     */
    public function delete(string $path, string|DefinitionInterface $definition): null|PictureInterface;
    
    /**
     * Deletes all created pictures with all its created images.
     *
     * @param string $path
     * @param string|DefinitionInterface $definition
     * @return array<array-key, PictureInterface> The deleted picture.
     */
    public function deleteAll(string|DefinitionInterface $definition): array;
    
    /**
     * Clear all created pictures.
     *
     * @return int The number of pictures cleared.
     */
    public function clear(): int;
}