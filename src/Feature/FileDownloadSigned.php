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
use Tobento\App\Media\FeaturesInterface;
use Tobento\Service\Routing\RouterInterface;

class FileDownloadSigned extends FileDownload
{
    use Traits\RestrictStorageTypesTrait;
    
    public const INFO = [
        'boot' => [
            'download files from file storages using signed routing',
        ],
    ];
    
    /**
     * @var array<int, string>
     */
    protected array $allowedStorageTypes = ['private'];
    
    /**
     * Returns the feature name.
     *
     * @return string
     */
    public function featureName(): string
    {
        return 'file-download-signed';
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
        $uri = $this->routeUri ?: 'media/s/download/{storage}/{path*}';
        
        $route = $router->get($uri, [$this, 'download'])->signed('media.file.download.signed');
        
        if ($this->routeDomain) {
            $route->domain($this->routeDomain);
        }
    }
}