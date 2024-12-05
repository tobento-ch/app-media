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

namespace Tobento\App\Media\Console;

use Tobento\App\Media\Feature;
use Tobento\App\Media\FeaturesInterface;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;
use Tobento\Service\Filesystem\Dir;

class IconsClearCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        icons:clear | Clears the cached icons.
    ';
    
    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param FeaturesInterface $features
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function handle(
        InteractorInterface $io,
        FeaturesInterface $features,
    ): int {
        foreach($features->all() as $feature) {
            if ($feature instanceof Feature\Icons) {
                (new Dir())->delete($feature->cacheDir()->dir());
                
                $io->success(sprintf('Cleared cached icons directory %s', $feature->cacheDir()->dir()));
            }
        }
        
        return 0;
    }
}