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
 
namespace Tobento\App\Media\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Media\FeatureInterface;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Media\Picture\PictureGenerator;
use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\App\Media\Picture\PictureRepository;
use Tobento\App\Media\Picture\PictureRepositoryInterface;
use Tobento\App\Media\Queue\PictureQueueHandlerInterface;
use Tobento\App\Media\Queue\PictureQueueHandler;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\Definitions;
use Tobento\Service\Picture\DefinitionsInterface;
use Tobento\Service\Picture\PictureTagInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Picture
 */
class Picture extends Boot implements FeatureInterface
{
    public const INFO = [
        'boot' => [
            'installs and loads media config file',
        ],
    ];

    public const BOOT = [
        \Tobento\App\Media\Boot\Media::class,
        \Tobento\App\Console\Boot\Console::class,
        
        // Misc:
        \Tobento\App\View\Boot\View::class,
        \Tobento\App\Queue\Boot\Queue::class,
        \Tobento\App\Cache\Boot\Cache::class, // needed for unique queue
        
        // FILE:
        \Tobento\App\FileStorage\Boot\FileStorage::class,
    ];
    
    /**
     * Create a new Picture instance.
     *
     * @param string $pictureStorageName The storage name where to store the generated picture data.
     * @param string $imageStorageName The storage name where to store each created image, must have urls.
     * @param string $queueName
     */
    public function __construct(
        protected string $pictureStorageName = 'picture-data',
        protected string $imageStorageName = 'images',
        protected string $queueName = 'file',
    ) {}
    
    /**
     * Returns the feature name.
     *
     * @return string
     */
    public function featureName(): string
    {
        return 'picture';
    }
    
    /**
     * Returns the feature group.
     *
     * @return string
     */
    public function featureGroup(): string
    {
        return 'picture';
    }
    
    /**
     * Boot application services.
     *
     * @param AppInterface $app
     * @return void
     */
    public function boot(AppInterface $app): void
    {
        $this->app = $app;
        
        // Add feature:
        $app->get(FeaturesInterface::class)->add($this);
        
        // Interfaces:
        $app->set(
            DefinitionsInterface::class,
            static function () use ($app) {
                $dirs = new Dirs();
                
                foreach($app->dirs()->sort()->group('views')->all() as $dir) {
                    $dirs->dir(dir: $dir->dir().'picture-definitions/');
                }
                
                return new Definitions\StackDefinitions(
                    'stack',
                    new Definitions\JsonFilesDefinitions(
                        name: 'view',
                        dirs: $dirs,
                    ),
                );
            }
        );
        
        $app->set(
            PictureRepositoryInterface::class,
            function (StoragesInterface $storages) {
                return new PictureRepository(
                    pictureStorageName: $this->pictureStorageName,
                    imageStorageName: $this->imageStorageName,
                    storages: $storages,
                );
            }
        );
        
        // Set picture queue handler only if queue is available:
        if ($app->has(QueueInterface::class)) {
            $app->set(
                PictureQueueHandlerInterface::class,
                function (QueueInterface $queue) {
                    return new PictureQueueHandler(
                        queue: $queue,
                        queueName: $this->queueName,
                    );
                }
            );
        }
        
        $app->set(PictureGeneratorInterface::class, PictureGenerator::class);
        
        // View macros:
        $app->on(
            ViewInterface::class,
            function(ViewInterface $view) {
                $view->addMacro('picture', [$this, 'generatePicture']);
            }
        );
        
        // Console commands:
        $app->on(ConsoleInterface::class, static function(ConsoleInterface $console): void {
            $console->addCommand(\Tobento\App\Media\Console\PictureClearCommand::class);
        });
    }
    
    /**
     * Generate a new picture from the given path, resource and definition.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition A named definition or definition instance.
     * @param bool $queue
     * @return PictureTagInterface
     */
    public function generatePicture(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $queue = true,
    ): PictureTagInterface {
        return $this->app->get(PictureGeneratorInterface::class)->generate(
            path: $path,
            resource: $resource,
            definition: $definition,
            queue: $queue,
        );
    }
}