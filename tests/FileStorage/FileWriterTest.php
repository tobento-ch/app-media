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
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/uploads-private/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/uploads-private/');
    }
    
    public function testImplementsFileWriterInterface()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads-private'),
        );
        
        $this->assertInstanceof(FileWriterInterface::class, $fileWriter);
    }
    
    public function testWriteFromStreamMethodWritesToFileStorage()
    {
        $storage = Factory::createFileStorage(name: 'uploads-private');
        
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
        $storage = Factory::createFileStorage(name: 'uploads-private');
        
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
    
    public function testCopyFileMethodCopiesFileWithinStorage()
    {
        $storage = Factory::createFileStorage(name: 'uploads-private');

        // Prepare a file to copy:
        $storage->write(
            path: 'foo/image.jpg',
            content: 'original-content'
        );

        $fileWriter = new FileWriter(
            storage: $storage,
            duplicates: FileWriter::RENAME,
        );

        // Ensure initial state:
        $this->assertTrue($storage->exists('foo/image.jpg'));
        $this->assertFalse($storage->exists('bar/image.jpg'));

        // Copy the file:
        $writeResponse = $fileWriter->copyFile(
            path: 'foo/image.jpg',
            folderPath: 'bar'
        );

        // Assertions:
        $this->assertInstanceOf(WriteResponseInterface::class, $writeResponse);
        $this->assertSame('bar/image.jpg', $writeResponse->path());
        $this->assertSame('image.jpg', $writeResponse->originalFilename());
        $this->assertSame('', $writeResponse->content()); // copy has no stream
        $this->assertInstanceOf(MessagesInterface::class, $writeResponse->messages());

        // Ensure file exists at new location:
        $this->assertTrue($storage->exists('bar/image.jpg'));

        // Ensure content is identical (no processing):
        $this->assertSame(
            'original-content',
            (string)$storage->with('stream')->file('bar/image.jpg')->stream()
        );
    }

    public function testCopyFileMethodHandlesDuplicates()
    {
        $storage = Factory::createFileStorage(name: 'uploads-private');

        // Prepare two files:
        $storage->write('foo/image.jpg', 'content-1');
        $storage->write('bar/image.jpg', 'content-2');

        $fileWriter = new FileWriter(
            storage: $storage,
            duplicates: FileWriter::RENAME,
        );

        // Copy foo/image.jpg into bar/ where image.jpg already exists:
        $writeResponse = $fileWriter->copyFile(
            path: 'foo/image.jpg',
            folderPath: 'bar'
        );

        // Should rename to image-1.jpg:
        $this->assertSame('bar/image-1.jpg', $writeResponse->path());
        $this->assertTrue($storage->exists('bar/image-1.jpg'));
        $this->assertSame('image.jpg', $writeResponse->originalFilename());
    }

    public function testFilenamesUsingAlnum()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads-private'),
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
            storage: Factory::createFileStorage(name: 'uploads-private'),
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
            storage: Factory::createFileStorage(name: 'uploads-private'),
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
            storage: Factory::createFileStorage(name: 'uploads-private'),
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
    
    public function testDuplicatesRename()
    {
        $storage = Factory::createFileStorage(name: 'uploads-private');

        $fileWriter = new FileWriter(
            storage: $storage,
            duplicates: FileWriter::RENAME,
        );

        $stream = Factory::createStreamFactory()->createStream('content');

        // 1. First write: foo.txt
        $writeResponse = $fileWriter->writeFromStream($stream, 'foo.txt', '');
        $this->assertSame('foo.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());

        // 2. Second write: foo-1.txt
        $writeResponse = $fileWriter->writeFromStream($stream, 'foo.txt', '');
        $this->assertSame('foo-1.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());

        // 3. Third write: foo-2.txt
        $writeResponse = $fileWriter->writeFromStream($stream, 'foo.txt', '');
        $this->assertSame('foo-2.txt', $writeResponse->path());
        $this->assertSame('foo.txt', $writeResponse->originalFilename());

        // 4. Write a file that already has a suffix: foo-3.txt
        $writeResponse = $fileWriter->writeFromStream($stream, 'foo-3.txt', '');
        $this->assertSame('foo-3.txt', $writeResponse->path());
        $this->assertSame('foo-3.txt', $writeResponse->originalFilename());

        // 5. Now writing foo-3.txt again should produce foo-4.txt
        $writeResponse = $fileWriter->writeFromStream($stream, 'foo-3.txt', '');
        $this->assertSame('foo-4.txt', $writeResponse->path());
        $this->assertSame('foo-3.txt', $writeResponse->originalFilename());

        // 6. Non-numeric suffix should not break: foo-bar.txt → foo-bar-1.txt
        $writeResponse = $fileWriter->writeFromStream($stream, 'foo-bar.txt', '');
        $this->assertSame('foo-bar.txt', $writeResponse->path());

        $writeResponse = $fileWriter->writeFromStream($stream, 'foo-bar.txt', '');
        $this->assertSame('foo-bar-1.txt', $writeResponse->path());

        // 7. Deep numeric suffix: foo-10.txt → foo-11.txt
        $writeResponse = $fileWriter->writeFromStream($stream, 'foo-10.txt', '');
        $this->assertSame('foo-10.txt', $writeResponse->path());

        $writeResponse = $fileWriter->writeFromStream($stream, 'foo-10.txt', '');
        $this->assertSame('foo-11.txt', $writeResponse->path());
    }
    
    public function testDublicatesOverwrite()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads-private'),
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
            storage: Factory::createFileStorage(name: 'uploads-private'),
            duplicates: 'invalid',
        );
        
        $stream = Factory::createStreamFactory()->createStream('content');
        
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
        $writeResponse = $fileWriter->writeFromStream(stream: $stream, filename: 'foo.txt', folderPath: '');
    }
    
    public function testFoldersUsingAlnum()
    {
        $fileWriter = new FileWriter(
            storage: Factory::createFileStorage(name: 'uploads-private'),
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
            storage: Factory::createFileStorage(name: 'uploads-private'),
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
            storage: Factory::createFileStorage(name: 'uploads-private'),
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
        $storage = Factory::createFileStorage(name: 'uploads-private');
        
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
                filename: __DIR__.'/../resources/uploads-private/image.jpg'
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