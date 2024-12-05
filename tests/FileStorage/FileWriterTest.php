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

namespace Tobento\App\Media\Test\FileStorage;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\FileWriter;
use Tobento\App\Media\FileStorage\FileWriterInterface;
use Tobento\App\Media\FileStorage\Writer;
use Tobento\App\Media\FileStorage\WriteResponseInterface;
use Tobento\App\Media\Image\ImageProcessor;
use Tobento\App\Media\Test\Factory;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Message\MessagesInterface;

class FileWriterTest extends TestCase
{
    public function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/uploads/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/uploads/');
    }
    
    public function testImplementsFileWriterInterface()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
        );
        
        $this->assertInstanceof(FileWriterInterface::class, $fileWriter);
    }
    
    public function testWriteFromStreamMethodWritesToFileStorage()
    {
        $storage = Factory::createFileStorage(name: 'uploads');
        
        $fileWriter = new FileWriter(
            storage: $storage,
        );
        
        $this->assertSame(0, count($storage->files(path: '')->all()));
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStream('content'),
            filename: 'file.txt',
            folderPath: '',
        );
        
        $this->assertSame(1, count($storage->files(path: '')->all()));
        $this->assertInstanceof(WriteResponseInterface::class, $writeResponse);
        $this->assertSame('file.txt', $writeResponse->path());
        $this->assertInstanceof(StreamInterface::class, $writeResponse->content());
        $this->assertSame('file.txt', $writeResponse->originalFilename());
        $this->assertInstanceof(MessagesInterface::class, $writeResponse->messages());
    }
    
    public function testWriteUploadedFileMethodWritesToFileStorage()
    {
        $storage = Factory::createFileStorage(name: 'uploads');
        
        $fileWriter = new FileWriter(
            storage: $storage,
        );
        
        $this->assertSame(0, count($storage->files(path: '')->all()));
        
        $writeResponse = $fileWriter->writeUploadedFile(
            file: Factory::createUploadedFileFactory()->createUploadedFile(
                stream: Factory::createStreamFactory()->createStream('content'),
                clientFilename: 'file.txt',
            ),
            folderPath: '',
        );
        
        $this->assertSame(1, count($storage->files(path: '')->all()));
        $this->assertInstanceof(WriteResponseInterface::class, $writeResponse);
        $this->assertSame('file.txt', $writeResponse->path());
        $this->assertInstanceof(StreamInterface::class, $writeResponse->content());
        $this->assertSame('file.txt', $writeResponse->originalFilename());
        $this->assertInstanceof(MessagesInterface::class, $writeResponse->messages());
    }

    public function testFilenamesUsingAlnum()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            filenames: FileWriter::ALNUM,
        );
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStream('content'),
            filename: 'foo Ba?r.txt',
            folderPath: '',
        );
        
        $this->assertSame('foo-Ba-r.txt', $writeResponse->path());
        $this->assertSame('foo Ba?r.txt', $writeResponse->originalFilename());
    }
    
    public function testFilenamesUsingRename()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            filenames: FileWriter::RENAME,
        );
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStream('content'),
            filename: 'foo.txt',
            folderPath: '',
        );
        
        $this->assertSame(44, strlen($writeResponse->path()));
        $this->assertSame('foo.txt', $writeResponse->originalFilename());
    }
    
    public function testFilenamesUsingKeep()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            filenames: FileWriter::KEEP,
        );
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStream('content'),
            filename: 'foo Bar.txt',
            folderPath: '',
        );
        
        $this->assertSame('foo Bar.txt', $writeResponse->path());
        $this->assertSame('foo Bar.txt', $writeResponse->originalFilename());
    }
    
    public function testFilenamesUsingClosure()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            filenames: function (string $filename): string {
                return 'custom';
            },
        );
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStream('content'),
            filename: 'foo.txt',
            folderPath: '',
        );
        
        $this->assertSame('custom.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());
    }
    
    public function testDublicatesRename()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            duplicates: FileWriter::RENAME,
        );
        
        $stream = Factory::createStreamFactory()->createStream('content');
        
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
        $this->assertSame('foo.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());
        
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
        $this->assertSame('foo-1.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());
        
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
        $this->assertSame('foo-2.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());
    }
    
    public function testDublicatesOverwrite()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            duplicates: FileWriter::OVERWRITE,
        );
        
        $stream = Factory::createStreamFactory()->createStream('content');
        
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
        $this->assertSame('foo.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());
        
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
        $this->assertSame('foo.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());
    }
    
    public function testDublicatesThrowsWriteExceptionIfInvalidParameterValue()
    {
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Not allowed to overwrite the file :path.');
        
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            duplicates: 'invalid',
        );
        
        $stream = Factory::createStreamFactory()->createStream('content');
        
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
    }
    
    public function testFoldersUsingAlnum()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            folders: FileWriter::ALNUM,
        );
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStream('content'),
            filename: 'file.txt',
            folderPath: 'foo Bar/ba?z',
        );
        
        $this->assertSame('foo-Bar/ba-z/file.txt', $writeResponse->path());
        $this->assertSame('file.txt', $writeResponse->originalFilename());
    }
    
    public function testFoldersUsingClosure()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            folders: function (string $path): string {
                return 'custom';
            },
        );
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStream('content'),
            filename: 'foo.txt',
            folderPath: 'bar',
        );
        
        $this->assertSame('custom/foo.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());
    }
    
    public function testFolderDepthLimitThrowsWriteExceptionIfExceeds()
    {
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Allowed folder depth of :num exceeded for the folder :path.');
        
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads'),
            folderDepthLimit: 2,
        );
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStream('content'),
            filename: 'file.txt',
            folderPath: 'foo/bar/baz',
        );
    }
    
    public function testUsingWriters()
    {
        $storage = Factory::createFileStorage(name: 'uploads');
        
        $fileWriter = new FileWriter(
            storage: $storage,
            writers: [
                new Writer\ImageWriter(
                    imageProcessor: new ImageProcessor(
                        actions: [
                            'orientate' => [],
                            'resize' => ['width' => 25],
                        ],
                    ),
                ),
            ],
        );
        
        $writeResponse = $fileWriter->writeFromStream(
            stream: Factory::createStreamFactory()->createStreamFromFile(
                filename: __DIR__.'/../resources/uploads/image.jpg'
            ),
            filename: 'image.jpg',
            folderPath: '',
        );
        
        $file = $storage->with('width')->file(path: 'image.jpg');
        $this->assertSame(25, $file->width());
        $this->assertSame('image.jpg', $writeResponse->path());
        $this->assertNotEmpty($writeResponse->content());
        $this->assertSame('image.jpg', $writeResponse->originalFilename());
        $this->assertSame('Auto orientated image.', $writeResponse->messages()->first()->message());
    }
}