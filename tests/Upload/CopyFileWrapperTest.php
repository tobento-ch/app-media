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

namespace Tobento\App\Media\Test\Upload;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Upload\CopyFileWrapper;

class CopyFileWrapperTest extends TestCase
{
    public function testWrapper()
    {
        $factory = new Psr17Factory();

        $uploadedFile = $factory->createUploadedFile(
            stream: $factory->createStream('content'),
            size: null,
            error: UPLOAD_ERR_OK,
            clientFilename: 'image.jpg',
            clientMediaType: 'image/jpeg',
        );
        
        $wrapper = new CopyFileWrapper(
            uploadedFile: $uploadedFile,
            storage: 'uploads',
            path: 'foo/image.jpg',
        );
        
        $this->assertSame($uploadedFile, $wrapper->uploadedFile());
        $this->assertSame('uploads', $wrapper->storage());
        $this->assertSame('foo/image.jpg', $wrapper->path());
    }
}