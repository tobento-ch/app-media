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

namespace Tobento\App\Media\Test;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Tobento\App\FileStorage\FilesystemStorageFactory;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\Storages;
use Tobento\Service\FileStorage\StoragesInterface;

class Factory
{
    public static function createFileStorage(
        string $name,
        null|string $folder = null,
        $withPublicUrl = true
    ): StorageInterface {
        $factory = new FilesystemStorageFactory(streamFactory: new Psr17Factory());
        $folder = $folder ?: $name;
        $config = [
            'location' => __DIR__.'/tmp/file-storage/'.$folder,
        ];
        
        if ($withPublicUrl) {
            $config['public_url'] = 'https://www.example.com/files/'.$folder;
        }
        
        return $factory->createStorage(name: $name, config: $config);
    }
    
    public static function createFileStorages(array $names = [], $withPublicUrl = true): StoragesInterface
    {
        $storages = new Storages();
        
        foreach($names as $name) {
            $storages->add(static::createFileStorage(name: $name, withPublicUrl: $withPublicUrl));
        }
        
        return $storages;
    }
    
    public static function createStreamFactory(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }
    
    public static function createUploadedFileFactory(): UploadedFileFactoryInterface
    {
        return new Psr17Factory();
    }
}