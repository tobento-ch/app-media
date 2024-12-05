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
use Tobento\App\Media\FeatureInterface;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Media\Icon\FallbackIcons;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Dir\Dir;
use Tobento\Service\Dir\DirInterface;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\Icon\IconFactory;
use Tobento\Service\Icon\IconFactoryInterface;
use Tobento\Service\Icon\IconInterface;
use Tobento\Service\Icon\IconNotFoundException;
use Tobento\Service\Icon\Icons as ServiceIcons;
use Tobento\Service\Icon\IconsInterface;
use Tobento\Service\Icon\StackIcons;
use Tobento\Service\Icon\SvgFileIconsToJsonFiles;
use Tobento\Service\View\ViewInterface;

/**
 * Icons
 */
class Icons extends Boot implements FeatureInterface
{
    public const INFO = [
        'boot' => [
            'supporting icons',
        ],
    ];

    public const BOOT = [
        \Tobento\App\Media\Boot\Media::class,
        \Tobento\App\Console\Boot\Console::class,
        \Tobento\App\View\Boot\View::class,
    ];
    
    /**
     * Create a new Icons instance.
     *
     * @param string $cacheDir
     * @param bool $throwIconNotFoundException
     */
    final public function __construct(
        protected string $cacheDir,
        protected bool $throwIconNotFoundException = false
    ) {}
    
    /**
     * Returns the feature name.
     *
     * @return string
     */
    public function featureName(): string
    {
        return 'icons';
    }
    
    /**
     * Returns the feature group.
     *
     * @return string
     */
    public function featureGroup(): string
    {
        return 'icons';
    }
    
    /**
     * Returns the cache dir.
     *
     * @return DirInterface
     */
    public function cacheDir(): DirInterface
    {
        return new Dir($this->cacheDir);
    }
    
    /**
     * Boot application services.
     *
     * @param AppInterface $app
     * @return void
     */
    public function boot(
        AppInterface $app,
    ): void {
        $this->app = $app;
        
        // Add feature:
        $app->get(FeaturesInterface::class)->add($this);
        
        // Interfaces:
        $app->set(IconFactoryInterface::class, IconFactory::class);
        
        $app->set(
            IconsInterface::class,
            function (IconFactoryInterface $iconFactory) use ($app) {
                $dirs = new Dirs();
                
                foreach($app->dirs()->sort()->group('views')->all() as $dir) {
                    $dirs->dir(dir: $dir->dir().'icons/');
                }
                
                return new StackIcons(
                    new SvgFileIconsToJsonFiles(
                        dirs: $dirs,
                        cacheDir: $this->cacheDir(),
                        clearCache: false,
                        iconFactory: $iconFactory,
                    ),
                    new FallbackIcons(
                        iconFactory: $iconFactory,
                        throwIconNotFoundException: $this->throwIconNotFoundException,
                    ),
                );
            }
        );
        
        // View macros:
        $app->on(
            ViewInterface::class,
            function(ViewInterface $view) {
                $view->addMacro('icon', [$this, 'getIcon']);
            }
        );
        
        // Console commands:
        $app->on(ConsoleInterface::class, static function(ConsoleInterface $console): void {
            $console->addCommand(\Tobento\App\Media\Console\IconsClearCommand::class);
        });
    }
    
    /**
     * Returns the icon.
     *
     * @param string $name
     * @return IconInterface
     * @throws IconNotFoundException
     */
    public function getIcon(string $name): IconInterface
    {
        return $this->app->get(IconsInterface::class)->get($name);
    }
}