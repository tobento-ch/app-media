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
 
namespace Tobento\App\Media\Upload;

use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\UploadedFileException;

/**
 * ValidatorInterface
 */
interface ValidatorInterface
{
    /**
     * Validates the uploaded file.
     *
     * @param UploadedFileInterface $file
     * @return void
     * @throws UploadedFileException
     */
    public function validateUploadedFile(UploadedFileInterface $file): void;
}