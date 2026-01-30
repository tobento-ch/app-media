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
use Tobento\Service\Support\Str;

class FileDisplay extends Boot implements FeatureInterface
{
    use Traits\RestrictStorageTypesTrait;
    
    public const INFO = [
        'boot' => [
            'display files from file storages',
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
     * @var array<int, string>
     */
    protected array $allowedStorageTypes = ['public'];
    
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
        return 'file-display';
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
        $uri = $this->routeUri ?: 'media/file/{storage}/{path*}';
        
        $route = $router->get($uri, [$this, 'display'])->name('media.file.display');
        
        if ($this->routeDomain) {
            $route->domain($this->routeDomain);
        }
    }
    
    /**
     * Display the file.
     *
     * @param string $storage
     * @param string $path
     * @param StoragesInterface $storages
     * @param ResponseFactoryInterface $responseFactory
     * @return ResponseInterface
     */
    public function display(
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
        
        $this->restrictStorageTypes(
            storage: $storage,
            allowedStorageTypes: $this->allowedStorageTypes,
            exception: static fn () => new NotFoundException(),
        );
        
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
            ->withHeader(
                'Content-Disposition',
                sprintf(
                    "inline; filename=\"%s\"; filename*=UTF-8''%s",
                    $this->asciiFallback($file->name()),
                    rawurlencode($file->name())
                )
            )
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
    
    /**
     * Returns an ASCII-only fallback filename for use in the
     * Content-Disposition header. Any non-ASCII characters are
     * replaced with underscores to ensure broad browser compatibility.
     *
     * @param string $filename The original filename.
     * @return string The sanitized ASCII fallback filename.
     */
    protected function asciiFallback(string $filename): string
    {
        $info = pathinfo($filename);

        $name = Str::slug($info['filename'], '-');
        $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';

        return $name . $ext;
    }
}