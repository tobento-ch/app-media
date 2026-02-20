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

namespace Tobento\App\Media\Test\Imager;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Tobento\App\Media\Imager\ImageActions;

class ImageActionsTest extends TestCase
{
    public function testLogWritesToProvidedLogger(): void
    {
        $logger = new Logger('test');
        $handler = new TestHandler();
        $logger->pushHandler($handler);

        $actions = new ImageActions()->setLogger($logger);

        // call protected log() via reflection
        $method = new \ReflectionClass($actions)->getMethod('log');
        $method->setAccessible(true);
        $method->invoke($actions, LogLevel::INFO, 'Test message', ['foo' => 'bar']);

        $this->assertTrue(
            $handler->hasRecord('Test message', Level::Info)
        );
    }

    public function testLogDoesNotFailWithoutLogger(): void
    {
        $actions = new ImageActions();

        $method = new \ReflectionClass($actions)->getMethod('log');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($actions, LogLevel::INFO, 'No logger available');

        $this->assertTrue(true);
    }

    public function testVendorBehaviorIsNotBroken(): void
    {
        $actions = new ImageActions();

        // Ensure vendor methods still exist
        $this->assertTrue(
            method_exists($actions, 'createAction')
        );
    }
}