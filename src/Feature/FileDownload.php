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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Media\FeatureInterface;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Routing\RouterInterface;

/**
 * FileDownload
 */
class FileDownload extends Boot implements FeatureInterface
{
    public const INFO = [
        'boot' => [
            'download files from file storages',
        ],
    ];

    public const BOOT = [
        \Tobento\App\Media\Boot\Media::class,
        
        // HTTP:
        \Tobento\App\Http\Boot\ErrorHandler::class,
        \Tobento\App\Http\Boot\Routing::class,
        
        // FILE:
        \Tobento\App\FileStorage\Boot\FileStorage::class,
    ];
    
    /**
     * Create a new FileDisplay instance.
     *
     * @param array $supportedStorages
     * @param null|string $routeUri
     * @param null|string $routeDomain
     */
    final public function __construct(
        protected array $supportedStorages = ['images'],
        protected null|string $routeUri = null,
        protected null|string $routeDomain = null,
    ) {}
    
    /**
     * Returns the feature name.
     *
     * @return string
     */
    public function featureName(): string
    {
        return 'file-download';
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
     * @param RouterInterface $router
     * @return void
     */
    public function boot(
        AppInterface $app,
        RouterInterface $router
    ): void {
        $this->app = $app;
        
        // Add feature:
        $app->get(FeaturesInterface::class)->add($this);
        
        // Routes:
        $uri = $this->routeUri ?: 'media/download/{storage}/{path*}';
        
        $route = $router->get($uri, [$this, 'download'])->name('media.file.download');
        
        if ($this->routeDomain) {
            $route->domain($this->routeDomain);
        }
    }
    
    /**
     * Download the file.
     *
     * @param string $storage
     * @param string $path
     * @param StoragesInterface $storages
     * @param ResponseFactoryInterface $responseFactory
     * @return ResponseInterface
     */
    public function download(
        string $storage,
        string $path,
        StoragesInterface $storages,
        ResponseFactoryInterface $responseFactory,
    ): ResponseInterface {
        if (! $storages->has($storage)) {
            throw new NotFoundException();
        }
        
        $storage = $storages->get($storage);
        
        if (! $this->supportsStorage($storage->name())) {
            throw new NotFoundException();
        }
        
        if (! $storage->exists(path: $path)) {
            throw new NotFoundException();
        }
        
        try {
            $file = $storage->with('stream', 'mimeType')->file(path: $path);
        } catch (FileNotFoundException $e) {
            throw new NotFoundException();
        }
        
        return $responseFactory->createResponse(200)
            ->withHeader('X-Exclude-Previous-Uri', '1')
            ->withHeader('Content-Type', (string)$file->mimeType())
            ->withHeader('Content-Disposition', 'attachment; filename='.$file->name())
            ->withHeader('Content-Length', (string)$file->size())
            ->withBody($file->stream());
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