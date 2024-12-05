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
use Tobento\App\Media\Feature\Picture;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\App\Media\Picture\PictureRepositoryInterface;
use Tobento\App\Media\Queue\PictureJobHandler;
use Tobento\App\Media\Queue\PictureQueueHandlerInterface;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Picture\DefinitionsInterface;
use Tobento\Service\Picture\PictureTagInterface;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\{directory};

class PictureTest extends \Tobento\App\Testing\TestCase
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
            }
        );
        
        return $app;
    }
    
    public function testFeature()
    {
        $this->fakeConfig()->with('media.features', [
            new Picture(),
        ]);
        
        $app = $this->bootingApp();
        
        $this->assertInstanceof(Picture::class, $app->get(FeaturesInterface::class)->get('picture'));
        $this->assertInstanceof(DefinitionsInterface::class, $app->get(DefinitionsInterface::class));
        $this->assertInstanceof(PictureRepositoryInterface::class, $app->get(PictureRepositoryInterface::class));
        $this->assertInstanceof(PictureQueueHandlerInterface::class, $app->get(PictureQueueHandlerInterface::class));
        $this->assertInstanceof(PictureGeneratorInterface::class, $app->get(PictureGeneratorInterface::class));
    }
    
    public function testConsoleCommandsAreAvailable()
    {
        $this->fakeConfig()->with('media.features', [
            new Picture(),
        ]);
        
        $app = $this->bootingApp();
        $console = $app->get(ConsoleInterface::class);
        
        $this->assertTrue($console->hasCommand('picture:clear'));
    }
    
    public function testDisplayPictureWorkflow()
    {
        $this->fakeConfig()->with('media.features', [
            new Picture(),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $app = $this->bootingApp();
        $view = $app->get(ViewInterface::class);
        $fakeQueue->clearQueue($fakeQueue->queue(name: 'file'));
        
        // first time, fallback picture and queued:
        $pic = $view->picture(path: 'image.jpg', resource: 'uploads', definition: 'product');
        $this->assertInstanceof(PictureTagInterface::class, $pic);
        $this->assertTrue(str_starts_with($pic->img()->attributes()->get('src'), 'data:image/jpeg;base64'));
        $fakeQueue->queue(name: 'file')->assertPushedTimes(PictureJobHandler::class, 1);
        
        // second time, fallback picture and not queued as unique:
        $pic = $view->picture(path: 'image.jpg', resource: 'uploads', definition: 'product');
        $this->assertInstanceof(PictureTagInterface::class, $pic);
        $this->assertTrue(str_starts_with($pic->img()->attributes()->get('src'), 'data:image/jpeg;base64'));
        $fakeQueue->queue(name: 'file')->assertPushedTimes(PictureJobHandler::class, 1);
        
        // third time with generated pics:
        $fakeQueue->runJobs($fakeQueue->queue(name: 'file')->getAllJobs());
        $pic = $view->picture(path: 'image.jpg', resource: 'uploads', definition: 'product');
        $app->get(PictureRepositoryInterface::class)->delete(path: 'image.jpg', definition: 'product');
        
        $this->assertInstanceof(PictureTagInterface::class, $pic);
        $this->assertTrue(str_ends_with($pic->img()->attributes()->get('src'), '.jpg'));
        $fakeQueue->queue(name: 'file')->assertPushedTimes(PictureJobHandler::class, 1);
        
        $fakeQueue->clearQueue($fakeQueue->queue(name: 'file'));
    }
}