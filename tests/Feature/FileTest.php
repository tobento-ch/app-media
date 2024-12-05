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

namespace Tobento\App\Media\Test\Feature;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppInterface;
use Tobento\App\FileStorage\FilesystemStorageFactory;
use Tobento\App\Media\Exception\FileException;
use Tobento\App\Media\Feature\File;
use Tobento\App\Media\FeaturesInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\{directory};

class FileTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Media\Boot\Media::class);
        
        $app->on(
            StoragesInterface::class,
            function(StoragesInterface $storages, FilesystemStorageFactory $factory) {
                $storage = $factory->createStorage(name: 'images', config: [
                    'location' => directory('public').'img/',
                    'public_url' => 'https://example.com/img/',
                ]);
                
                $storage->createFolder(path: 'path');
                $storage->write(path: 'path/file.txt', content: 'content');
                
                $storages->add($storage);
            }
        );
        
        return $app;
    }
    
    public function testFeature()
    {
        $this->fakeConfig()->with('media.features', [
            new File(),
        ]);
        
        $app = $this->bootingApp();
        $features = $app->get(FeaturesInterface::class);
        
        $this->assertInstanceof(File::class, $features->get('file'));
    }
    
    public function testFileStorageMethodReturnsUrl()
    {
        $this->fakeConfig()->with('media.features', [
            new File(),
        ]);
        
        $app = $this->bootingApp();
        
        $fileUrl = $app->get(File::class)->storage(storage: 'images')->file(path: 'path/file.txt')->url();
        
        $this->assertSame('https://example.com/img/path/file.txt', $fileUrl);
    }
    
    public function testFileStorageMethodReturnsEmptyUrlIfNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new File(),
        ]);
        
        $app = $this->bootingApp();
        
        $fileUrl = $app->get(File::class)->storage(storage: 'images')->file(path: 'path/file1.txt')->url();
        
        $this->assertSame('', $fileUrl);
    }

    public function testFileUrlMethodReturnsUrl()
    {
        $this->fakeConfig()->with('media.features', [
            new File(),
        ]);
        
        $app = $this->bootingApp();
        
        $fileUrl = $app->get(File::class)->url(storage: 'images', path: 'path/file.txt');
        
        $this->assertSame('https://example.com/img/path/file.txt', $fileUrl);
    }
    
    public function testFileUrlMethodReturnsEmptyUrlIfNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new File(),
        ]);
        
        $app = $this->bootingApp();
        
        $fileUrl = $app->get(File::class)->url(storage: 'images', path: 'path/file1.txt');
        
        $this->assertSame('', $fileUrl);
    }
    
    public function testFileReturnsEmptyUrlIfStorageNotFound()
    {
        $this->fakeConfig()->with('media.features', [
            new File(),
        ]);
        
        $app = $this->bootingApp();
        
        $fileUrl = $app->get(File::class)->storage(storage: 'unknown')->file(path: 'path/file.txt')->url();
        
        $this->assertSame('', $fileUrl);
    }
    
    public function testFileViewMacrosAreAvailable()
    {
        $this->fakeConfig()->with('media.features', [
            new File(),
        ]);
        
        $app = $this->getApp();
        $app->boot(\Tobento\App\View\Boot\View::class);
        $app = $this->bootingApp();
        $view = $app->get(ViewInterface::class);
        
        $this->assertSame(
            'https://example.com/img/path/file.txt',
            $view->fileStorage(storage: 'images')->file(path: 'path/file.txt')->url()
        );
        
        $this->assertSame(
            'https://example.com/img/path/file.txt',
            $view->fileUrl(storage: 'images', path: 'path/file.txt')
        );
    }
    
    public function testIfStorageIsNotSupportedReturnsEmptyUrl()
    {
        $this->fakeConfig()->with('media.features', [
            new File(supportedStorages: []),
        ]);
        
        $app = $this->bootingApp();
        
        $fileUrl = $app->get(File::class)->storage(storage: 'images')->file(path: 'path/file.txt')->url();
        
        $this->assertSame('', $fileUrl);
    }
    
    public function testThrowsFileExceptionIfNotFoundStorage()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('File storage unkown not found');
        
        $this->fakeConfig()->with('media.features', [
            new File(supportedStorages: [], throw: true),
        ]);
        
        $app = $this->bootingApp();
        $fileUrl = $app->get(File::class)->storage(storage: 'unkown')->file(path: 'path/file.txt')->url();
    }
    
    public function testThrowsFileExceptionIfNotSupportedStorage()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('File storage images not supported');
        
        $this->fakeConfig()->with('media.features', [
            new File(supportedStorages: [], throw: true),
        ]);
        
        $app = $this->bootingApp();
        $fileUrl = $app->get(File::class)->storage(storage: 'images')->file(path: 'path/file.txt')->url();
    }
}