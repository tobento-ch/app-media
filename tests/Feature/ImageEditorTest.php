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
use Tobento\App\Media\Event;
use Tobento\App\Media\Feature\ImageEditor;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Language\Languages;
use function Tobento\App\{directory};

class ImageEditorTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Media\Test\App\Boot\MediaFiles::class);
        $app->boot(\Tobento\App\Media\Boot\Media::class);
        return $app;
    }
    
    public function testFeature()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(),
        ]);
        
        $app = $this->bootingApp();
        
        $this->assertInstanceof(ImageEditor::class, $app->get(FeaturesInterface::class)->get('image-editor'));
    }

    public function testNotDisplaysEditorIfTemplateNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/unknown/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfStorageNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads', 'foo'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/default/foo/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: [],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/default/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfImageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/default/uploads/unknown.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfNotImage()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/default/uploads/executable.php');
        
        $http->response()->assertStatus(404);
    }
    
    public function testNotDisplaysEditorIfNoPermission()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/default/uploads/image.jpg');
        
        $http->response()->assertStatus(403);
    }
    
    public function testDisplaysEditor()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/default/uploads/image.jpg');
        
        $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'X-Exclude-Previous-Uri', value: '1')
            ->assertBodyContains('Image Editor')
            ->assertBodyContains('<img src="data:image/jpeg');
    }

    public function testDisplaysEditorWithCustomTemplate()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                templates: [
                    'custom' => ['background'],
                ],
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/custom/uploads/image.jpg');
        
        $http->response()->assertStatus(200);
    }
    
    public function testDisplaysEditorWithSpecificActionsOnly()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                templates: [
                    'default' => ['background'],
                ],
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'media/image-editor/default/uploads/image.jpg');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('data-image-action="background"')
            ->assertBodyNotContains('data-image-action="crop"');
    }
    
    public function testDisplaysEditorInLocaleDe()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
                localizeRoute: true,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'de/media/image-editor/default/uploads/image.jpg');
        
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
            ->assertBodyContains('Bild-Editor');
    }
    
    public function testPreviewsImage()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor-preview/default/uploads/image.jpg')
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
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor-preview/unknown/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfStorageNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads', 'foo'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor-preview/default/foo/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: [],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor-preview/default/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfImageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor-preview/default/uploads/unknown.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfNotImage()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor-preview/default/uploads/executable.php');
        
        $http->response()->assertStatus(404);
    }
    
    public function testPreviewFailsIfNoPermission()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor-preview/default/uploads/image.jpg');
        
        $http->response()->assertStatus(403);
    }
    
    public function testPreviewFailsIfImagerProcessingFails()
    {
        // will fail as unsupported mime type.
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
                supportedMimeTypes: ['image/gif'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor-preview/default/uploads/image.jpg');
        
        $http->response()->assertStatus(422);
    }
    
    public function testUpdatesImage()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $http->previousUri('prev-uri');
        $http->request(method: 'POST', uri: 'media/image-editor/default/uploads/image.jpg')
            ->body([
                'actions' => [
                    'brightness' => ['brightness' => '20'],
                ],
                'format' => 'image/gif',
            ]);
        
        $app = $this->bootingApp();
        $storage = $app->get(StoragesInterface::class)->get('uploads');
        $storage->delete(path: 'image.gif');
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation(uri: 'prev-uri');

        $events->assertDispatched(Event\ImageEdited::class, static function(Event\ImageEdited $event): bool {
            return $event->storageName() === 'uploads'
                && $event->file()->path() === 'image.jpg';
        });
        
        $this->assertTrue($storage->exists(path: 'image.gif'));
        $storage->delete(path: 'image.gif');
    }
    
    public function testUpdateFailsIfTemplateNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor/unknown/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfStorageNotExists()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads', 'foo'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor/default/foo/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfStorageIsNotSupported()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: [],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor/default/uploads/image.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfImageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor/default/uploads/unknown.jpg');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfNotImage()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor/default/uploads/executable.php');
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateFailsIfNoPermission()
    {
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor/default/uploads/image.jpg');
        
        $http->response()->assertStatus(403);
    }
    
    public function testUpdateFailsIfImagerProcessingFails()
    {
        // will fail as unsupported mime type.
        $this->fakeConfig()->with('media.features', [
            new ImageEditor(
                supportedStorages: ['uploads'],
                userPermission: null,
                supportedMimeTypes: ['image/gif'],
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'media/image-editor/default/uploads/image.jpg');
        
        $http->response()->assertStatus(422);
    }
}