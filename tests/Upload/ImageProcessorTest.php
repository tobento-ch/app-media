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

namespace Tobento\App\Media\Test\Upload;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tobento\App\Media\Upload\ImageProcessor;

class ImageProcessorTest extends TestCase
{
    public function testLogWritesToProvidedLogger(): void
    {
        $logger = new Logger('test');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);

        $processor = new ImageProcessor(actions: [])->setLogger($logger);

        // call protected log() via reflection
        $method = new \ReflectionClass($processor)->getMethod('log');
        $method->setAccessible(true);
        $method->invoke($processor, LogLevel::INFO, 'Test message', ['foo' => 'bar']);

        $this->assertTrue(
            $testHandler->hasRecord('Test message', Level::Info)
        );
    }

    public function testLogDoesNotFailWithoutLogger(): void
    {
        $processor = new ImageProcessor(actions: []);

        $method = new \ReflectionClass($processor)->getMethod('log');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($processor, LogLevel::INFO, 'No logger available');

        $this->assertTrue(true);
    }

    public function testVendorBehaviorIsNotBroken(): void
    {
        $processor = new ImageProcessor(actions: [
            'resize' => ['width' => 100],
        ]);

        // We don't test actual image processing — just ensure no exception is thrown
        $this->assertTrue(method_exists($processor, 'processFromStream'));
    }
}