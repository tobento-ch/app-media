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
use Tobento\App\Media\Event;
use Tobento\App\Media\Feature;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Media\Queue\PictureJobHandler;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Language\Languages;
use function Tobento\App\{directory};

class PictureEditorTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Media\Test\App\Boot\MediaFiles::class);
        $app->boot(\Tobento\App\Media\Boot\Media::class);
        
        $app->on(
            StoragesInterface::class,
            function(StoragesInterface $storages, FilesystemStorageFactory $factory) {
                $storage = $factory->createStorage(name: 'picture-data', config: [
                    'location' => directory('app').'storage/picture-data/',
                ]);
                
                $storages->add($storage);
                
                $storage = $factory->createStorage(name: 'pics', config: [
                    'location' => directory('public').'pics/',
                ]);
                
                $storages->add($storage);
            }
        );
        
        return $app;
    }
    
    public function testFeature()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\PictureEditor(),
        ]);
        
        $app = $this->bootingApp();
        
        $this->assertInstanceof(Feature\PictureEditor::class, $app->get(FeaturesInterface::class)->get('picture-editor'));
    }

    public function testNotDisplaysEditorIfTemplateNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/unkown/uploads/image.jpg',
            query: ['definitions' => ['product']],
        );
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfStorageNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads', 'foo'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/foo/image.jpg',
            query: ['definitions' => ['product']],
        );
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: [],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => ['product']],
        );
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfImageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/unknown.jpg',
            query: ['definitions' => ['product']],
        );
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfNotImage()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/executable.php',
            query: ['definitions' => ['product']],
        );
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfNoPermission()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => ['product']],
        );
        
        $http->response()->assertStatus(403);
    }
    
    public function testDisplaysEditorWithoutDefinitions()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => []],
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'X-Exclude-Previous-Uri', value: '1')
            ->assertBodyContains('Picture Editor')
            ->assertBodyContains('There are no images available to edit.');
    }
    
    public function testDisplaysEditorWithDefinitionsNotExisting()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => ['unknown']],
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'X-Exclude-Previous-Uri', value: '1')
            ->assertBodyContains('Picture Editor')
            ->assertBodyContains('There are no images available to edit.');
    }
    
    public function testDisplaysEditorWithInvalidDefinitionsValue()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => 'invalid'],
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'X-Exclude-Previous-Uri', value: '1')
            ->assertBodyContains('Picture Editor')
            ->assertBodyContains('There are no images available to edit.');
    }
    
    public function testDisplaysEditor()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => ['product']],
        );
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'X-Exclude-Previous-Uri', value: '1')
            ->assertBodyContains('Picture Editor')
            ->assertBodyContains('<img src="data:image/jpeg');
        
        $this->assertCount(1, $response->crawl()->filter('.picture-editor-definition'));
        $this->assertCount(2, $response->crawl()->filter('.picture-editor-src'));
    }
    
    public function testDisplaysEditorWithMultipleDefinitions()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => ['product', 'product-list']],
        );
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'X-Exclude-Previous-Uri', value: '1')
            ->assertBodyContains('Picture Editor')
            ->assertBodyContains('<img src="data:image/jpeg');
        
        $this->assertCount(2, $response->crawl()->filter('.picture-editor-definition'));
        $this->assertCount(5, $response->crawl()->filter('.picture-editor-src'));
    }

    public function testDisplaysEditorWithCustomTemplate()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                templates: [
                    'custom' => ['background'],
                ],
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/custom/uploads/image.jpg',
            query: ['definitions' => ['product']],
        );
        
        $http->response()->assertStatus(200);
    }
    
    public function testDisplaysEditorWithSpecificActionsOnly()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                templates: [
                    'default' => ['background'],
                ],
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => ['product']],
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('data-image-action="background"')
            ->assertBodyNotContains('data-image-action="crop"');
    }
    
    public function testDisplaysEditorInLocaleDe()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
                localizeRoute: true,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'de/media/picture-editor/default/uploads/image.jpg',
            query: ['definitions' => ['product']],
        );
        
        $app = $this->getApp();
        $app->on(LanguagesInterface::class, function() {
            $languageFactory = new LanguageFactory();
            return new Languages(
                $languageFactory->createLanguage(locale: 'en', default: true),
                $languageFactory->createLanguage(locale: 'de', slug: 'de'),
            );
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Bilder-Editor');
    }
    
    public function testPreviewsImage()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor-preview/default/uploads/image.jpg')
            ->body([
                'actions' => [
                    'brightness' => ['brightness' => '20'],
                ],
            ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertContentType('application/json')
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(items: 2)
                     ->has(key: 'file', items: 5)
                     ->has(key: 'file.mimetype', value: 'image/jpeg')
                     ->has(key: 'file.width', value: 200)
                     ->has(key: 'file.height', value: 150)
                     ->has(key: 'file.size', value: '12.16 KB')
                     ->has(key: 'file.dataUrl', passes: fn (string $url) => is_string($url) && $url !== '')
                     ->has(key: 'messages', items: 2)
            );
    }
    
    public function testPreviewFailsIfTemplateNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor-preview/unknown/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfStorageNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads', 'foo'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor-preview/default/foo/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: [],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor-preview/default/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfImageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor-preview/default/uploads/unknown.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfNotImage()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor-preview/default/uploads/executable.php');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfNoPermission()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor-preview/default/uploads/image.jpg');
        
        $http->response()->assertStatus(403);
    }
    
    public function testPreviewFailsIfImagerProcessingFails()
    {
        // will fail as unsupported mime type.
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
                supportedMimeTypes: ['image/gif'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor-preview/default/uploads/image.jpg');
        
        $http->response()->assertStatus(422);
    }
    
    public function testUpdatesPicture()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $http->previousUri('prev-uri');
        $http->request(method: 'POST', uri: 'media/picture-editor/default/uploads/image.jpg')
            ->body([
                'definitions' => [
                    'product' => [
                        'img' => [
                            'src' => [
                                'actions' => ['brightness' => ['brightness' => '20']],
                            ],
                            'srcset' => [
                                ['actions' => ['brightness' => ['brightness' => '20']]]
                            ]
                        ],
                        'sources' => [
                            ['srcset' => [['actions' => ['brightness' => ['brightness' => '20']]]]]
                        ],
                    ],
                ],
            ]);
        
        $app = $this->bootingApp();
        $fakeQueue->clearQueue($fakeQueue->queue(name: 'file'));
        $storage = $app->get(StoragesInterface::class)->get('images');

        $http->response()
            ->assertStatus(302)
            ->assertLocation(uri: 'prev-uri');
        
        $events->assertDispatched(Event\PictureEdited::class, static function(Event\PictureEdited $event): bool {
            return $event->storageName() === 'uploads'
                && $event->file()->path() === 'image.jpg';
        });
        
        $fakeQueue->queue(name: 'file')->assertPushedTimes(PictureJobHandler::class, 1);
        $fakeQueue->clearQueue($fakeQueue->queue(name: 'file'));
    }
    
    public function testUpdatesPictureWithoutQueue() {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(
                imageStorageName: 'pics',
            ),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
                queuePictureGeneration: false,
            ),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $http->previousUri('prev-uri');
        $http->request(method: 'POST', uri: 'media/picture-editor/default/uploads/image.jpg')
            ->body([
                'definitions' => [
                    'product-list' => [
                        'img' => [
                            'src' => [
                                'actions' => ['brightness' => ['brightness' => '20']],
                            ],
                            'srcset' => [
                                ['actions' => ['brightness' => ['brightness' => '20']]]
                            ]
                        ],
                        'sources' => [
                            ['srcset' => [['actions' => ['brightness' => ['brightness' => '20']]]]]
                        ],
                    ],
                ],
            ]);
        
        $app = $this->bootingApp();
        $storage = $app->get(StoragesInterface::class)->get('pics');

        $http->response()
            ->assertStatus(302)
            ->assertLocation(uri: 'prev-uri');

        $this->assertSame(3, count($storage->files(path: '')->all()));
        
        $events->assertDispatched(Event\PictureEdited::class, static function(Event\PictureEdited $event): bool {
            return $event->storageName() === 'uploads'
                && $event->file()->path() === 'image.jpg';
        });
        
        $fakeQueue->queue(name: 'file')->assertNotPushed(PictureJobHandler::class);
    }
    
    public function testUpdateFailsIfTemplateNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor/unknown/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfStorageNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads', 'foo'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor/default/foo/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: [],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor/default/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfImageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor/default/uploads/unknown.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfNotImage()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor/default/uploads/executable.php');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfNoPermission()
    {
        $this->fakeConfig()->with('media.features', [
            new Feature\Picture(),
            new Feature\PictureEditor(
                supportedStorages: ['uploads'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/picture-editor/default/uploads/image.jpg');
        
        $http->response()->assertStatus(403);
    }
}