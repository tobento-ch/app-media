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
use Tobento\App\Media\Feature\Icons;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Logging\LoggersInterface;
use Tobento\App\Testing\Logging\LogEntry;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Icon\IconFactoryInterface;
use Tobento\Service\Icon\IconInterface;
use Tobento\Service\Icon\IconNotFoundException;
use Tobento\Service\Icon\IconsInterface;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\{directory};

class IconsTest extends \Tobento\App\Testing\TestCase
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
        $app = $this->bootingApp();
        
        $this->assertInstanceof(Icons::class, $app->get(FeaturesInterface::class)->get('icons'));
        $this->assertInstanceof(IconFactoryInterface::class, $app->get(IconFactoryInterface::class));
        $this->assertInstanceof(IconsInterface::class, $app->get(IconsInterface::class));
    }
    
    public function testViewMacrosAreAvailable()
    {
        $app = $this->bootingApp();
        $view = $app->get(ViewInterface::class);
        
        $this->assertInstanceof(IconInterface::class, $view->icon(name: 'edit'));
    }
    
    public function testConsoleCommandsAreAvailable()
    {
        $app = $this->bootingApp();
        $console = $app->get(ConsoleInterface::class);
        
        $this->assertTrue($console->hasCommand('icons:clear'));
    }
    
    public function testIconsAreLoadedFromWithinViewDirectory()
    {
        $app = $this->bootingApp();
        $view = $app->get(ViewInterface::class);
        
        $this->assertSame('<span class="icon icon-edit"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 100 100" aria-hidden="true"><title>edit</title><path d="M80,40L30,90L0,100l10-30l50-50 M90,30l10-10L80,0L70,10L90,30z"/></svg></span>', (string)$view->icon(name: 'edit'));
    }
    
    public function testIconsFallback()
    {
        $app = $this->bootingApp();
        $view = $app->get(ViewInterface::class);
        
        $this->assertInstanceof(IconInterface::class, $view->icon(name: 'unknown'));
    }
    
    public function testThrowsIconNotFoundExceptionIfConfigured()
    {
        $this->expectException(IconNotFoundException::class);
        
        $this->fakeConfig()->with('media.features', [
            new Icons(
                cacheDir: directory('app').'storage/icons/',
                throwIconNotFoundException: true,
            ),
        ]);
        
        $app = $this->bootingApp();
        $app->get(ViewInterface::class)->icon(name: 'unknown');
    }
    
    public function testLogsIconNotFoundException()
    {
        $fakeLogging = $this->fakeLogging();
        $app = $this->getApp();
        $app->boot(\Tobento\App\Logging\Boot\Logging::class);
        $app = $this->bootingApp();
        $app->get(ViewInterface::class)->icon(name: 'unknown');
        
        $fakeLogging->logger()
            ->assertLogged(fn (LogEntry $log): bool =>
                $log->level === 'warning'
                && $log->message === 'Icon unknown not found'
            );
    }
}