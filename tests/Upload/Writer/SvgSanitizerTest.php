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

namespace Tobento\App\Media\Test\Upload\Writer;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Tobento\App\Media\Upload\Writer\SvgSanitizer;

class SvgSanitizerTest extends TestCase
{
    public function testLogWritesToProvidedLogger(): void
    {
        $logger = new Logger('test');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);

        $sanitizer = new SvgSanitizer(logger: $logger)->setLogger($logger);

        // Call protected log() via reflection
        $method = new \ReflectionClass($sanitizer)->getMethod('log');
        $method->setAccessible(true);
        $method->invoke($sanitizer, LogLevel::INFO, 'Test message', ['foo' => 'bar']);

        $this->assertTrue(
            $testHandler->hasRecord('Test message', Level::Info)
        );
    }

    public function testLogDoesNotFailWithoutLogger(): void
    {
        $sanitizer = new SvgSanitizer();

        $method = new \ReflectionClass($sanitizer)->getMethod('log');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($sanitizer, LogLevel::INFO, 'No logger available');

        $this->assertTrue(true);
    }

    public function testVendorBehaviorIsNotBroken(): void
    {
        $sanitizer = new SvgSanitizer();

        // BaseSvgSanitizer has a write() method
        $this->assertTrue(method_exists($sanitizer, 'write'));
    }
}