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
use Tobento\App\Media\Feature\FileDownloadSigned;
use Tobento\App\Media\FeaturesInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Routing\RouterInterface;
use function Tobento\App\{directory};

class FileDownloadSignedTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Media\Boot\Media::class);
        
        $app->on(
            StoragesInterface::class,
            function(StoragesInterface $storages, FilesystemStorageFactory $factory) {
                $storage = $factory->createStorage(name: 'private', config: [
                    'location' => directory('app').'storage/signed-private/',
                    'storage_type' => 'private',
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
            new FileDownloadSigned(),
        ]);
        
        $app = $this->bootingApp();
        $features = $app->get(FeaturesInterface::class);
        
        $this->assertInstanceof(FileDownloadSigned::class, $features->get('file-download-signed'));
    }
    
    public function testDisplaysFile()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['private']),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        $http->response();
        
        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);
        $url = $router->url('media.file.download.signed', ['storage' => 'private', 'path' => 'path/grünwald.txt'])->sign();
        
        $http->request(method: 'GET', uri: (string)$url);
        
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
            new FileDownloadSigned(supportedStorages: ['private']),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        $http->response();
        
        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);
        $url = $router->url('media.file.download.signed', ['storage' => 'private', 'path' => 'path/not-exists.txt'])->sign();
        
        $http->request(method: 'GET', uri: (string)$url);
        
        $http->response()->assertStatus(404);
    }
    
    public function testReturnsNotFoundResponseIfStorageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['not-exists']),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        $http->response();
        
        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);
        $url = $router->url('media.file.download.signed', ['storage' => 'not-exists', 'path' => 'path/grünwald.txt'])->sign();
        
        $http->request(method: 'GET', uri: (string)$url);
        
        $http->response()->assertStatus(404);
    }
    
    public function testReturnsNotFoundResponseIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: []),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        $http->response();
        
        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);
        $url = $router->url('media.file.download.signed', ['storage' => 'private', 'path' => 'path/grünwald.txt'])->sign();
        
        $http->request(method: 'GET', uri: (string)$url);
        
        $http->response()->assertStatus(404);
    }
    
    public function testDisplaysFileUsingCustomRouteUri()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(
                supportedStorages: ['private'],
                routeUri: 'asset/{storage}/{path*}',
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        $http->response();
        
        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);
        $url = (string)$router->url('media.file.download.signed', ['storage' => 'private', 'path' => 'path/grünwald.txt'])->sign();

        $http->request(method: 'GET', uri: $url);
        
        $this->assertStringContainsString('asset/', $url);
        $http->response()->assertStatus(200);
    }
    
    public function testDisplaysFileUsingCustomRouteDomain()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(
                supportedStorages: ['private'],
                routeDomain: 'media.example.com',
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        $http->response();
        
        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);
        $url = (string)$router->url('media.file.download.signed', ['storage' => 'private', 'path' => 'path/grünwald.txt'])->sign();

        $http->request(method: 'GET', uri: $url);
        
        $this->assertStringContainsString('media.example.com', $url);
        $http->response()->assertStatus(200);
    }
    
    public function testReturnsNotFoundResponseIfStorageIsPublic()
    {
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(
                StoragesInterface::class,
                function(StoragesInterface $storages, FilesystemStorageFactory $factory) {
                    $private = $factory->createStorage(name: 'public', config: [
                        'location' => directory('app').'storage/signed-public/',
                        'storage_type' => 'public',
                    ]);

                    $private->createFolder(path: 'path');
                    $private->write(path: 'path/file.txt', content: 'secret');

                    $storages->add($private);
                }
            );
        });

        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['public']),
        ]);

        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        $http->response();
        
        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);
        $url = (string)$router->url('media.file.download.signed', ['storage' => 'public', 'path' => 'path/file.txt'])->sign();

        $http->request(method: 'GET', uri: $url);
        
        $http->response()->assertStatus(404);
    }

    public function testSignedUrlWithFutureExpirationWorks()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['private']),
        ]);

        $http = $this->fakeHttp();
        $http->request('GET', '');
        $http->response();

        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);

        $url = $router->url('media.file.download.signed', [
            'storage' => 'private',
            'path' => 'path/file.txt',
        ])->sign(expiration: time() + 3600);

        $http->request('GET', (string)$url);

        $http->response()->assertStatus(200);
    }
    
    public function testSignedUrlWithPastExpirationFails()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['private']),
        ]);

        $http = $this->fakeHttp();
        $http->request('GET', '');
        $http->response();

        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);

        $url = $router->url('media.file.download.signed', [
            'storage' => 'private',
            'path' => 'path/file.txt',
        ])->sign(expiration: time() - 10);

        $http->request('GET', (string)$url);

        $http->response()->assertStatus(403);
    }
    
    public function testSignedUrlWithQueryParametersWorks()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['private']),
        ]);

        $http = $this->fakeHttp();
        $http->request('GET', '');
        $http->response();

        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);

        $url = $router->url('media.file.download.signed', [
            'storage' => 'private',
            'path' => 'path/file.txt',
        ])->sign(withQuery: true);

        $http->request('GET', (string)$url);

        $http->response()->assertStatus(200);
    }
    
    public function testSignedUrlFailsIfQueryIsTampered()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['private']),
        ]);

        $http = $this->fakeHttp();
        $http->request('GET', '');
        $http->response();

        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);

        $url = $router->url('media.file.download.signed', [
            'storage' => 'private',
            'path' => 'path/file.txt',
        ])->sign(withQuery: true);

        // Tamper with query
        $tampered = $url.'&foo=baz';

        $http->request('GET', (string)$tampered);

        $http->response()->assertStatus(403);
    }
    
    public function testUnsignedUrlFails()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['private']),
        ]);

        $http = $this->fakeHttp();
        $http->request('GET', '');
        $http->response();

        // Build the URL WITHOUT signing it
        $app = $this->bootingApp();
        $router = $app->get(RouterInterface::class);

        $url = (string)$router->url('media.file.download.signed', [
            'storage' => 'private',
            'path' => 'path/file.txt',
        ]);

        // Make the request
        $http->request('GET', $url);

        // Unsigned URLs must fail
        $http->response()->assertStatus(403);
    }
}