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
use Tobento\Service\Imager\Response\Encoded;
use Tobento\Service\Message\MessagesInterface;

/**
 * Event after an image has been edited successfully.
 */
final class ImageEdited
{
    /**
     * Create a new ImageEdited.
     *
     * @param string $storageName
     * @param FileInterface $file
     * @param Encoded $encoded
     * @param MessagesInterface $messages
     */
    public function __construct(
        private string $storageName,
        private FileInterface $file,
        private Encoded $encoded,
        private MessagesInterface $messages,
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
     * Returns the encoded.
     *
     * @return Encoded
     */
    public function encoded(): Encoded
    {
        return $this->encoded;
    }
    
    /**
     * Returns the messages.
     *
     * @return MessagesInterface
     */
    public function messages(): MessagesInterface
    {
        return $this->messages;
    }
}