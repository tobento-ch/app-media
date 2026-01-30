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

class FileDisplaySigned extends FileDisplay
{
    use Traits\RestrictStorageTypesTrait;
    
    public const INFO = [
        'boot' => [
            'display files from file storages using signed routing',
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
        return 'file-display-signed';
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
        $uri = $this->routeUri ?: 'media/s/file/{storage}/{path*}';
        
        $route = $router->get($uri, [$this, 'display'])->signed('media.file.display.signed');
        
        if ($this->routeDomain) {
            $route->domain($this->routeDomain);
        }
    }
}