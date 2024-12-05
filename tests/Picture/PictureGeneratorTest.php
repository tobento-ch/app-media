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

namespace Tobento\App\Media\Test\Picture;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Picture\PictureGenerator;
use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\App\Media\Picture\PictureRepository;
use Tobento\App\Media\Picture\PictureRepositoryInterface;
use Tobento\App\Media\Queue\PictureQueueHandler;
use Tobento\App\Media\Queue\PictureQueueHandlerInterface;
use Tobento\App\Media\Test\Factory;
use Tobento\Service\Cache\ArrayCacheItemPool;
use Tobento\Service\Cache\Simple\Psr6Cache;
use Tobento\Service\Clock\FrozenClock;
use Tobento\Service\Container\Container;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Imager\Resource\File;
use Tobento\Service\Picture\Definition\ArrayDefinition;
use Tobento\Service\Picture\Definitions\Definitions;
use Tobento\Service\Picture\DefinitionsInterface;
use Tobento\Service\Picture\NullPictureTag;
use Tobento\Service\Picture\PictureTagInterface;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\QueueInterface;

class PictureGeneratorTest extends TestCase
{
    public function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/generated-picture-data/');
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/generated-images/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/generated-picture-data/');
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/generated-images/');
    }

    protected function createContainer(): ContainerInterface
    {
        $container = new Container();
        $pool = new ArrayCacheItemPool(clock: new FrozenClock());
        $cache = new Psr6Cache(pool: $pool, namespace: 'ns', ttl: null);
        $container->set(CacheInterface::class, $cache);
        
        $storages = Factory::createFileStorages(['generated-picture-data', 'generator-uploads'], withPublicUrl: false);
        $storages->add(Factory::createFileStorage('generated-images', withPublicUrl: true));
        $image = file_get_contents(__DIR__.'/../resources/uploads/image.jpg');
        $storages->get('generator-uploads')->write(path: 'image.jpg', content: $image);
        $storages->get('generator-uploads')->write(path: 'file.txt', content: 'text');
        $storages->get('generated-images')->write(path: 'foo/image.jpg', content: $image);
        $container->set(StoragesInterface::class, $storages);
        
        $pictureRepository = new PictureRepository(
            pictureStorageName: 'generated-picture-data',
            imageStorageName: 'generated-images',
            storages: $storages,
        );
        $container->set(PictureRepositoryInterface::class, $pictureRepository);
        
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: new JobProcessor($container));
        $container->set(QueueInterface::class, $queue);
        
        $definitions = new Definitions(
            'shop',
            new ArrayDefinition('product-main', ['img' => ['src' => [50]]]),
        );
        
        $container->set(DefinitionsInterface::class, $definitions);
        
        $queueHandler = new PictureQueueHandler(
            queue: $container->get(QueueInterface::class),
            queueName: 'primary',
        );
        $container->set(PictureQueueHandlerInterface::class, $queueHandler);
        
        return $container;
    }
    
    protected function createPictureGenerator(ContainerInterface $container, $withQueueHandler = true): PictureGeneratorInterface
    {
        $logger = new Logger('name');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);
        $container->set(TestHandler::class, $testHandler);
        
        $pictureGenerator = new PictureGenerator(
            pictureRepository: $container->get(PictureRepositoryInterface::class),
            storages: $container->get(StoragesInterface::class),
            definitions: $container->get(DefinitionsInterface::class),
            queueHandler: $withQueueHandler ? $container->get(PictureQueueHandlerInterface::class) : null,
            pictureCreator: null,
        );
        
        $pictureGenerator->setLogger($logger);
        return $pictureGenerator;
    }
    
    public function testPictureRepositoryMethod()
    {
        $generator = $this->createPictureGenerator($this->createContainer());
        
        $this->assertInstanceof(PictureRepositoryInterface::class, $generator->pictureRepository());
    }
    
    public function testGenerateMethodGeneratesPictureIfNotGenerated()
    {
        $container = $this->createContainer();
        $queue = $container->get(QueueInterface::class);
        $generator = $this->createPictureGenerator($container);
        $generator->pictureRepository()->delete(path: 'image.jpg', definition: 'product-main');
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: false,
        );
        
        $this->assertNotNull($generator->pictureRepository()->findOne(path: 'image.jpg', definition: 'product-main'));
        $this->assertTrue(str_ends_with($pictureTag->img()->attributes()->get('src'), '.jpg'));
        
        $generator->pictureRepository()->delete(path: 'image.jpg', definition: 'product-main');
    }
    
    public function testGenerateMethodReturnsGeneratedPictureIfExists()
    {
        $container = $this->createContainer();
        $queue = $container->get(QueueInterface::class);
        $generator = $this->createPictureGenerator($container);
        $generator->pictureRepository()->delete(path: 'image.jpg', definition: 'product-main');
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: false,
        );
        
        $this->assertTrue(str_ends_with($pictureTag->img()->attributes()->get('src'), '.jpg'));
        
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: 'uploads',
            definition: 'product-main',
            queue: true,
        );
        
        $this->assertSame(0, $queue->size());
        $this->assertTrue(str_ends_with($pictureTag->img()->attributes()->get('src'), '.jpg'));
        
        $generator->pictureRepository()->delete(path: 'image.jpg', definition: 'product-main');
    }
    
    public function testGenerateMethodIfGenerationFailsReturnsFallbackImageAndLogs()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-not-exists',
            queue: true,
        );
        
        $this->assertFalse(empty($pictureTag->img()->attributes()->get('src')));
        $this->assertTrue($container->get(TestHandler::class)->hasRecordThatContains('Generating picture for path image.jpg failed:', 'warning'));
    }
    
    public function testGenerateMethodQueuesByDefault()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $queue = $container->get(QueueInterface::class);
        
        $this->assertSame(0, $queue->size());
        
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
        );
        
        $this->assertSame(1, $queue->size());
        $this->assertInstanceof(PictureTagInterface::class, $pictureTag);
    }
    
    public function testGenerateMethodQueuesWithUniqueJob()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $queue = $container->get(QueueInterface::class);
        
        $this->assertSame(0, $queue->size());
        
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: true,
        );
        
        $this->assertSame(1, $queue->size());
        $this->assertInstanceof(PictureTagInterface::class, $pictureTag);
        
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: true,
        );
        
        $this->assertSame(1, $queue->size());
        $this->assertInstanceof(PictureTagInterface::class, $pictureTag);
    }
    
    public function testGenerateMethodCreatesFallbackImageFromFileUrlIfExists()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $pictureTag = $generator->generate(
            path: 'foo/image.jpg',
            resource: 'generated-images',

            definition: 'product-main',
            queue: true,
        );

        $this->assertSame(
            'https://www.example.com/files/generated-images/foo/image.jpg',
            $pictureTag->img()->attributes()->get('src')
        );
    }
    
    public function testGenerateMethodCreatesFallbackImageFromFileStreamIfUrlNotExists()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: true,
        );
        
        $this->assertTrue(str_starts_with($pictureTag->img()->attributes()->get('src'), 'data:image/jpeg;base64'));
    }
    
    public function testGenerateMethodCreatesFallbackImageFromResourceFile()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $pictureTag = $generator->generate(
            path: 'image.jpg',
            resource: new File(__DIR__.'/../tmp/file-storage/generator-uploads/image.jpg'),
            definition: 'product-main',
            queue: true,
        );
        
        $this->assertTrue(str_starts_with($pictureTag->img()->attributes()->get('src'), 'data:image/jpeg;base64'));
    }
    
    public function testGenerateMethodReturnsNullPictureTagAndLogsIfCreatingFallbackImageFails()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $pictureTag = $generator->generate(
            path: 'file.txt',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: true,
        );
        
        $this->assertInstanceof(NullPictureTag::class, $pictureTag);
        $this->assertTrue($container->get(TestHandler::class)->hasRecordThatContains('Creating fallback picture for path file.txt failed:', 'warning'));
    }
    
    public function testRegenerateMethodRegeneratesPicture()
    {
        $container = $this->createContainer();
        $queue = $container->get(QueueInterface::class);
        $generator = $this->createPictureGenerator($container);
        $generator->pictureRepository()->delete(path: 'image.jpg', definition: 'product-main');
        $pictureTag = $generator->regenerate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: false,
        );
        
        $this->assertNotNull($generator->pictureRepository()->findOne(path: 'image.jpg', definition: 'product-main'));
        $this->assertTrue(str_ends_with($pictureTag->img()->attributes()->get('src'), '.jpg'));
        
        $generator->pictureRepository()->delete(path: 'image.jpg', definition: 'product-main');
    }
    
    public function testRegenerateMethodIfGenerationFailsReturnsFallbackImageAndLogs()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $pictureTag = $generator->regenerate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-not-exists',
            queue: true,
        );
        
        $this->assertFalse(empty($pictureTag->img()->attributes()->get('src')));
        $this->assertTrue($container->get(TestHandler::class)->hasRecordThatContains('Regenerating picture for path image.jpg failed:', 'warning'));
    }
    
    public function testRegenerateMethodQueuesByDefault()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $queue = $container->get(QueueInterface::class);
        
        $this->assertSame(0, $queue->size());
        
        $pictureTag = $generator->regenerate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
        );
        
        $this->assertSame(1, $queue->size());
        $this->assertInstanceof(PictureTagInterface::class, $pictureTag);
    }
    
    public function testRegenerateMethodQueuesNotUniqueJob()
    {
        $container = $this->createContainer();
        $generator = $this->createPictureGenerator($container);
        $queue = $container->get(QueueInterface::class);
        
        $this->assertSame(0, $queue->size());
        
        $pictureTag = $generator->regenerate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: true,
        );
        
        $this->assertSame(1, $queue->size());
        $this->assertInstanceof(PictureTagInterface::class, $pictureTag);
        
        $pictureTag = $generator->regenerate(
            path: 'image.jpg',
            resource: 'generator-uploads',
            definition: 'product-main',
            queue: true,
        );
        
        $this->assertSame(2, $queue->size());
        $this->assertInstanceof(PictureTagInterface::class, $pictureTag);
    }
}