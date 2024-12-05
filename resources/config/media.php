<?php
/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\App\Media\Feature;
use function Tobento\App\{directory};

return [
    
    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Specify and configure the features you wish to use.
    |
    | See: https://github.com/tobento-ch/app-media#features
    |
    */
    
    'features' => [
        new Feature\Icons(
            cacheDir: directory('app').'storage/icons/',
            throwIconNotFoundException: false,
        ),
        
        new Feature\File(
            supportedStorages: ['images'],
        ),
    ],
        
];