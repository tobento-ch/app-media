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
use Psr\Container\ContainerInterface;
use Tobento\App\Media\Console\PictureClearCommand;
use Tobento\App\Media\Picture\PictureRepository;
use Tobento\App\Media\Picture\PictureRepositoryInterface;
use Tobento\App\Media\Test\Factory;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\FileStorage\StoragesInterface;

class PictureClearCommandTest extends TestCase
{
    public function getContainer(): ContainerInterface
    {
        $container = new Container();
        
        $container->set(StoragesInterface::class, function () {
            return Factory::createFileStorages(['picture-data', 'images']);
        });
        
        $container->set(PictureRepositoryInterface::class, function (StoragesInterface $storages) {
            return new PictureRepository(
                pictureStorageName: 'picture-data',
                imageStorageName: 'images',
                storages: $storages,
            );
        });
        
        return $container;
    }

    public function testClearsAllPictures()
    {
        $container = $this->getContainer();
        $repo = $container->get(PictureRepositoryInterface::class);
        
        (new TestCommand(
            command: PictureClearCommand::class,
            input: [],
        ))
        ->expectsOutput('Cleared all 0 generated pictures')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testClearsSpecificDefinitionPictures()
    {
        $container = $this->getContainer();
        $repo = $container->get(PictureRepositoryInterface::class);
        
        (new TestCommand(
            command: PictureClearCommand::class,
            input: ['--def' => ['product']],
        ))
        ->expectsOutput(sprintf('Cleared 0 generated pictures for the definition %s', 'product'))
        ->expectsExitCode(0)
        ->execute($container);
    }
}