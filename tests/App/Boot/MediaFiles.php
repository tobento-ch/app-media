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
 
namespace Tobento\App\Media\Test\App\Boot;

use Tobento\App\Boot;
use Tobento\App\Migration\Boot\Migration;

class MediaFiles extends Boot
{
    public const BOOT = [
        Migration::class,
        \Tobento\App\View\Boot\View::class,
    ];

    public function boot(Migration $migration): void
    {
        // install migration:
        $migration->install(\Tobento\App\Media\Test\App\Migration\MediaFiles::class);
    }
}