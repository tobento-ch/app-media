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
 
namespace Tobento\App\Media\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Boot\Functions;
use Tobento\App\Media\FeatureInterface;
use Tobento\App\Media\Features;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Migration\Boot\Migration;

/**
 * Media
 */
class Media extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads media config file',
            'implements media interfaces',
            'boots features from media config'
        ],
    ];

    public const BOOT = [
        Functions::class,
        Config::class,
        Migration::class,
    ];

    /**
     * Boot application services.
     *
     * @param Config $config
     * @param Migration $migration
     * @return void
     */
    public function boot(Config $config, Migration $migration): void
    {
        // install migration:
        $migration->install(\Tobento\App\Media\Migration\Media::class);
        
        // load the media config:
        $config = $config->load('media.php');

        // features:
        $features = new Features();
        $this->app->set(FeaturesInterface::class, $features);
        
        foreach($config['features'] ?? [] as $feature) {
            if (is_string($feature)) {
                $feature = $this->app->make($feature);
            }
                        
            if ($feature instanceof FeatureInterface) {
                $this->app->boot($feature);
                $features->add($feature);
            }
        }
    }
}