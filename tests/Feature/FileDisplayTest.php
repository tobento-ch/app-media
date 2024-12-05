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
use Tobento\App\Media\Feature\FileDisplay;
use Tobento\App\Media\FeaturesInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use function Tobento\App\{directory};

class FileDisplayTest extends \Tobento\App\Testing\TestCase
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
            new FileDisplay(),
        ]);
        
        $app = $this->bootingApp();
        $features = $app->get(FeaturesInterface::class);
        
        $this->assertInstanceof(FileDisplay::class, $features->get('file-display'));
    }
    
    public function testDisplaysFile()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDisplay(),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/file/images/path/file.txt');
        
        $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'Content-type', value: 'text/plain')
            ->assertHasHeader(name: 'Content-Disposition', value: 'inline; filename=file.txt')
            ->assertHasHeader(name: 'Content-Length', value: '7')
            ->assertHasHeader(name: 'X-Exclude-Previous-Uri', value: '1');
    }
    
    public function testReturnsNotFoundResponseIfFileDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDisplay(),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/file/images/image.png');
        
        $http->response()->assertStatus(404);
    }
    
    public function testReturnsNotFoundResponseIfStorageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDisplay(),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/file/uploads/image.png');
        
        $http->response()->assertStatus(404);
    }
    
    public function testReturnsNotFoundResponseIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDisplay(supportedStorages: []),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/file/images/path/file.txt');
        
        $http->response()->assertStatus(404);
    }
    
    public function testDisplaysFileUsingCustomRouteUri()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDisplay(
                routeUri: 'asset/{storage}/{path*}',
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'asset/images/path/file.txt');
        
        $http->response()->assertStatus(200);
    }
    
    public function testDisplaysFileUsingCustomRouteDomain()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDisplay(
                routeDomain: 'media.example.com',
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'http://media.example.com/media/file/images/path/file.txt');
        
        $http->response()->assertStatus(200);
    }
}