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
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\CreateUploadedFileException;
use Tobento\App\Media\Test\Factory;
use Tobento\App\Media\Upload\UploadedFileFactory;
use Tobento\App\Media\Upload\UploadedFileFactoryInterface;

class UploadedFileFactoryTest extends TestCase
{
    public function testThatImplementsInterface()
    {
        $factory = new UploadedFileFactory(
            uploadedFileFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        );
        
        $this->assertInstanceof(UploadedFileFactoryInterface::class, $factory);
    }
    
    public function testCreateFromRemoteUrl()
    {
        $factory = new UploadedFileFactory(
            uploadedFileFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        );
        
        $uploadedFile = $factory->createFromRemoteUrl(
            url: 'https://docs.tobento.ch/favicon.ico'
        );
        
        $this->assertInstanceof(UploadedFileInterface::class, $uploadedFile);
        $this->assertInstanceof(StreamInterface::class, $uploadedFile->getStream());
        $this->assertSame(3262, $uploadedFile->getSize());
        $this->assertSame('favicon.ico', $uploadedFile->getClientFilename());
        $this->assertSame(null, $uploadedFile->getClientMediaType());
    }
    
    public function testCreateFromRemoteUrlThrowsCreateUploadedFileExceptionIfNotFound()
    {
        $this->expectException(CreateUploadedFileException::class);
        $this->expectExceptionMessage('Creating uploaded file from remote file :url failed as not found.');
        
        $factory = new UploadedFileFactory(
            uploadedFileFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        );
        
        $uploadedFile = $factory->createFromRemoteUrl(
            url: 'https://docs.tobento.ch/abcdefgh'
        );
    }
    
    public function testCreateFromStorageFile()
    {
        $storage = Factory::createFileStorage(name: 'upload-factory');
        $storage->write(path: 'file.txt', content: 'content');
        
        $factory = new UploadedFileFactory(
            uploadedFileFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        );
        
        $uploadedFile = $factory->createFromStorageFile(
            file: $storage->with('stream')->file('file.txt')
        );
        
        $storage->delete(path: 'file.txt');
        
        $this->assertInstanceof(UploadedFileInterface::class, $uploadedFile);
        $this->assertInstanceof(StreamInterface::class, $uploadedFile->getStream());
        $this->assertSame(7, $uploadedFile->getSize());
        $this->assertSame('file.txt', $uploadedFile->getClientFilename());
        $this->assertSame(null, $uploadedFile->getClientMediaType());
    }
    
    public function testCreateFromStorageFileThrowsCreateUploadedFileExceptionIfNoStream()
    {
        $this->expectException(CreateUploadedFileException::class);
        $this->expectExceptionMessage('Writing storage file :file failed as no stream available.');
        
        $storage = Factory::createFileStorage(name: 'upload-factory');
        $storage->write(path: 'file.txt', content: 'content');
        
        $factory = new UploadedFileFactory(
            uploadedFileFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        );
        
        $uploadedFile = $factory->createFromStorageFile(
            file: $storage->file('file.txt')
        );
        
        $storage->delete(path: 'file.txt');
    }    
}