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
 
namespace Tobento\App\Media\FileStorage\Writer;

use Psr\Http\Message\StreamInterface;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\WriteResponseInterface;

/**
 * WriterInterface
 */
interface WriterInterface
{
    /**
     * Write.
     *
     * @param string $path
     * @param StreamInterface $stream
     * @param string $originalFilename
     * @return null|WriteResponseInterface Null if not supports writing for the given path and stream.
     * @throws WriteException
     */
    public function write(
        string $path,
        StreamInterface $stream,
        string $originalFilename,
    ): null|WriteResponseInterface;
}