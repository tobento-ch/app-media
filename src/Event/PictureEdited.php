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

namespace Tobento\App\Media\Event;

use Tobento\Service\FileStorage\FileInterface;

/**
 * Event after a picture has been edited successfully.
 */
final class PictureEdited
{
    /**
     * Create a new ImageEdited.
     *
     * @param string $storageName
     * @param FileInterface $file
     * @param array<array-key, string> $definitionNames
     */
    public function __construct(
        private string $storageName,
        private FileInterface $file,
        private array $definitionNames,
    ) {}

    /**
     * Returns the storage name.
     *
     * @return string
     */
    public function storageName(): string
    {
        return $this->storageName;
    }
    
    /**
     * Returns the file.
     *
     * @return FileInterface
     */
    public function file(): FileInterface
    {
        return $this->file;
    }
    
    /**
     * Returns the definition names.
     *
     * @return array<array-key, string>
     */
    public function definitionNames(): array
    {
        return $this->definitionNames;
    }
}