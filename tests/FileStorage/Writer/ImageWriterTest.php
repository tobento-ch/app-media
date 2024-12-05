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

namespace Tobento\App\Media\Test\FileStorage\Writer;

use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\Writer\ImageWriter;
use Tobento\App\Media\FileStorage\Writer\WriterInterface;
use Tobento\App\Media\FileStorage\WriteResponseInterface;
use Tobento\App\Media\Image\ImageProcessor;
use Tobento\App\Media\Test\Factory;
use Tobento\Service\Imager\Response\Encoded;
use Tobento\Service\Message\MessagesInterface;

class ImageWriterTest extends TestCase
{
    public function testImplementsWriterInterface()
    {
        $writer = new ImageWriter(
            imageProcessor: new ImageProcessor(),
        );
        
        $this->assertInstanceof(WriterInterface::class, $writer);
    }
    
    public function testWriteMethod()
    {
        $writer = new ImageWriter(
            imageProcessor: new ImageProcessor(
                actions: [
                    'orientate' => [],
                    'resize' => ['width' => 25],
                ],
            ),
        );
        
        $writeResponse = $writer->write(
            path: 'image.jpg',
            stream: Factory::createStreamFactory()->createStreamFromFile(
                filename: __DIR__.'/../../resources/uploads/image.jpg'
            ),
            originalFilename: 'orgfilename.jpg',
        );
        
        $encoded = $writeResponse->content();
        $this->assertInstanceof(Encoded::class, $encoded);
        $this->assertSame(25, $encoded->width());
        $this->assertSame('image.jpg', $writeResponse->path());
        $this->assertSame('orgfilename.jpg', $writeResponse->originalFilename());
        $this->assertSame('Auto orientated image.', $writeResponse->messages()->first()->message());
    }
    
    public function testWriteMethodRebuildsPathIfExtensionChanges()
    {
        $writer = new ImageWriter(
            imageProcessor: new ImageProcessor(
                actions: [
                    'orientate' => [],
                    'resize' => ['width' => 25],
                ],
                convert: ['image/jpeg' => 'image/gif'],
            ),
        );
        
        $writeResponse = $writer->write(
            path: 'foo/bar/image.jpg',
            stream: Factory::createStreamFactory()->createStreamFromFile(
                filename: __DIR__.'/../../resources/uploads/image.jpg'
            ),
            originalFilename: 'orgfilename.jpg',
        );
        
        $this->assertSame('foo/bar/image.gif', $writeResponse->path());
        $this->assertSame('orgfilename.jpg', $writeResponse->originalFilename());
    }
    
    public function testWriteMethodReturnsNullIfUnsupportedFile()
    {
        $writer = new ImageWriter(
            imageProcessor: new ImageProcessor(
                actions: [
                    'orientate' => [],
                    'resize' => ['width' => 25],
                ],
                convert: ['image/jpeg' => 'image/gif'],
            ),
        );
        
        $writeResponse = $writer->write(
            path: 'foo.txt',
            stream: Factory::createStreamFactory()->createStream('content'),
            originalFilename: 'foo.txt',
        );
        
        $this->assertNull($writeResponse);
    }
}