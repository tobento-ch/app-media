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
 
namespace Tobento\App\Media\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Media\Exception\FileException;
use Tobento\App\Media\FeatureInterface;
use Tobento\App\Media\FeaturesInterface;
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\NullStorage;
use Tobento\Service\FileStorage\ReadOnlyStorageAdapter;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\View\ViewInterface;

/**
 * File
 */
class File extends Boot implements FeatureInterface
{
    public const INFO = [
        'boot' => [
            'retrieve files from file storages',
        ],
    ];

    public const BOOT = [
        \Tobento\App\Media\Boot\Media::class,
        \Tobento\App\FileStorage\Boot\FileStorage::class,
    ];
    
    /**
     * Create a new File instance.
     *
     * @param array $supportedStorages
     * @param bool $throw
     */
    final public function __construct(
        protected array $supportedStorages = ['images'],
        protected bool $throw = false,
    ) {}
    
    /**
     * Returns the feature name.
     *
     * @return string
     */
    public function featureName(): string
    {
        return 'file';
    }
    
    /**
     * Returns the feature group.
     *
     * @return string
     */
    public function featureGroup(): string
    {
        return 'file';
    }
    
    /**
     * Boot application services.
     *
     * @param AppInterface $app
     * @return void
     */
    public function boot(AppInterface $app): void
    {
        $this->app = $app;
        
        // Add feature:
        $app->get(FeaturesInterface::class)->add($this);
        
        // View macro:
        $app->on(
            ViewInterface::class,
            function(ViewInterface $view) {
                $view->addMacro('fileStorage', [$this, 'storage']);
                $view->addMacro('fileUrl', [$this, 'url']);
            }
        );
    }
    
    /**
     * Returns the file storage.
     *
     * @param string $storage
     * @return StorageInterface
     */
    public function storage(string $storage): StorageInterface
    {
        $storages = $this->app->get(StoragesInterface::class);
        
        if (! $storages->has($storage)) {
            if (! $this->throw) {
                return new NullStorage();
            }
            
            throw new FileException(sprintf('File storage %s not found', $storage));
        }
        
        $storage = $storages->get($storage);
        
        if (! $this->supportsStorage($storage->name())) {
            if (! $this->throw) {
                return new NullStorage();
            }
            
            throw new FileException(sprintf('File storage %s not supported', $storage->name()));
        }
        
        return new ReadOnlyStorageAdapter(storage: $storage->with('url'), throw: $this->throw);
    }
    
    /**
     * Returns a file url.
     *
     * @param string $storage
     * @param string $path
     * @return string
     */
    public function url(string $storage, string $path): string
    {
        return (string)$this->storage($storage)->file(path: $path)->url();
    }
    
    /**
     * Returns true if the given storage is supported, otherwise false.
     *
     * @param string $storage
     * @return bool
     */
    protected function supportsStorage(string $storage): bool
    {
        return in_array($storage, $this->supportedStorages);
    }
}