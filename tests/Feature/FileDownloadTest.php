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
use Tobento\App\Media\Feature\FileDownload;
use Tobento\App\Media\FeaturesInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use function Tobento\App\{directory};

class FileDownloadTest extends \Tobento\App\Testing\TestCase
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
                    'storage_type' => 'public',
                ]);
                
                $storage->createFolder(path: 'path');
                $storage->write(path: 'path/file.txt', content: 'content');
                $storage->write(path: 'path/grünwald.txt', content: 'content');
                
                $storages->add($storage);
            }
        );
        
        return $app;
    }

    public function testFeature()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(),
        ]);
        
        $app = $this->bootingApp();
        $features = $app->get(FeaturesInterface::class);
        
        $this->assertInstanceof(FileDownload::class, $features->get('file-download'));
    }
    
    public function testDownloadsFile()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/download/images/path/grünwald.txt');
        
        $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'Content-type', value: 'text/plain')
            ->assertHasHeader(name: 'Content-Disposition', value: 'attachment; filename="gruenwald.txt"; filename*=UTF-8\'\'gr%C3%BCnwald.txt')
            ->assertHasHeader(name: 'Content-Length', value: '7')
            ->assertHasHeader(name: 'X-Exclude-Previous-Uri', value: '1');
    }
    
    public function testReturnsNotFoundResponseIfFileDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/download/images/image.png');
        
        $http->response()->assertStatus(404);
    }
    
    public function testReturnsNotFoundResponseIfStorageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/download/uploads/image.png');
        
        $http->response()->assertStatus(404);
    }
    
    public function testReturnsNotFoundResponseIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(supportedStorages: []),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/download/images/path/file.txt');
        
        $http->response()->assertStatus(404);
    }
    
    public function testDownloadsFileUsingCustomRouteUri()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(
                routeUri: 'download/{storage}/{path*}',
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'download/images/path/file.txt');
        
        $http->response()->assertStatus(200);
    }
    
    public function testDownloadsFileUsingCustomRouteDomain()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(
                routeDomain: 'media.example.com',
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'http://media.example.com/media/download/images/path/file.txt');
        
        $http->response()->assertStatus(200);
    }
    
    public function testReturnsNotFoundResponseIfStorageIsPrivate()
    {
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(
                StoragesInterface::class,
                function(StoragesInterface $storages, FilesystemStorageFactory $factory) {
                    $private = $factory->createStorage(name: 'private', config: [
                        'location' => directory('app').'storage/private/',
                        'storage_type' => 'private',
                    ]);

                    $private->createFolder(path: 'path');
                    $private->write(path: 'path/file.txt', content: 'secret');

                    $storages->add($private);
                }
            );
        });

        $this->fakeConfig()->with('media.features', [
            new FileDownload(supportedStorages: ['private']),
        ]);

        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'http://media.example.com/media/download/private/path/file.txt');

        $http->response()->assertStatus(404);
    }
}