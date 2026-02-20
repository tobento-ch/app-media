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

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Tobento\App\AppInterface;
use Tobento\App\Media\Feature\Picture;
use Tobento\App\Media\Picture\PictureGenerator;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;

class PictureGeneratorTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Media\Boot\Media::class);
        return $app;
    }

    public function testLogs()
    {
        // Enable the Picture feature
        $this->fakeConfig()->with('media.features', [
            new Picture(),
        ]);

        $app = $this->bootingApp();

        // Resolve the generator from the container
        $generator = $app->get(PictureGeneratorInterface::class);

        // Ensure we got the wrapped generator
        $this->assertInstanceOf(PictureGenerator::class, $generator);

        // Prepare logger
        $logger = new Logger('test');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);

        // Assign logger via LoggerTrait
        $generator->setLogger($logger);

        // Call protected log() via reflection
        $method = new \ReflectionClass($generator)->getMethod('log');
        $method->setAccessible(true);
        $method->invoke($generator, LogLevel::INFO, 'Test message', ['foo' => 'bar']);

        // Assert log was written
        $this->assertTrue(
            $testHandler->hasRecord('Test message', Level::Info)
        );
    }
}