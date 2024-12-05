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

namespace Tobento\App\Media\Test\Icon;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Icon\FallbackIcons;
use Tobento\Service\Icon\IconFactory;
use Tobento\Service\Icon\IconInterface;
use Tobento\Service\Icon\IconNotFoundException;
use Tobento\Service\Icon\IconsInterface;

class FallbackIconsTest extends TestCase
{
    public function testThatImplementsIconsInterface()
    {
        $this->assertInstanceof(
            IconsInterface::class,
            new FallbackIcons(iconFactory: new IconFactory())
        );
    }
    
    public function testGetMethod()
    {
        $icons = new FallbackIcons(iconFactory: new IconFactory());
        
        $this->assertInstanceof(
            IconInterface::class,
            $icons->get('download')
        );
    }
    
    public function testGetMethodLogs()
    {
        $logger = new Logger('name');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);
        
        $icons = new FallbackIcons(iconFactory: new IconFactory());
        $icons->setLogger($logger);
        $icon = $icons->get('download');
        
        $this->assertTrue($testHandler->hasRecord('Icon download not found', 'warning'));
    }
    
    public function testGetMethodThrowsIconNotFoundExceptionIfConfigured()
    {
        $this->expectException(IconNotFoundException::class);
        
        $icons = new FallbackIcons(
            iconFactory: new IconFactory(),
            throwIconNotFoundException: true,
        );
        
        $icon = $icons->get('download');
    }
    
    public function testHasMethod()
    {
        $icons = new FallbackIcons(iconFactory: new IconFactory());
        
        $this->assertTrue($icons->has('download'));
    }
}