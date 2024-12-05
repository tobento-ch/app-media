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

namespace Tobento\App\Media\Test\Console;

use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Console\IconsClearCommand;
use Tobento\App\Media\Feature\Icons;
use Tobento\App\Media\Features;
use Tobento\App\Media\FeaturesInterface;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Filesystem\File;

class IconsClearCommandTest extends TestCase
{
    public function testClearsIcons()
    {
        $iconFeature = new Icons(
            cacheDir: realpath(__DIR__.'/../').'/tmp/icons/',
        );
        
        $dir = new Dir();
        $file = new File(realpath(__DIR__.'/../').'/resources/icons/edit.svg');
        $file->copy($iconFeature->cacheDir()->dir().'edit.svg');
        
        $container = new Container();
        $features = new Features();
        $features->add($iconFeature);
        $container->set(FeaturesInterface::class, $features);

        $this->assertSame(1, count($dir->getFiles($iconFeature->cacheDir()->dir())));
        
        (new TestCommand(command: IconsClearCommand::class))
            ->expectsOutput('Cleared cached icons directory '.$iconFeature->cacheDir())
            ->expectsExitCode(0)
            ->execute($container);
        
        $this->assertSame(0, count($dir->getFiles($iconFeature->cacheDir()->dir())));
    }
}