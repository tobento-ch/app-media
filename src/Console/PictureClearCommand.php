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

use Tobento\App\Media\Picture\PictureRepositoryInterface;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;

class PictureClearCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        picture:clear | Clears the generated pictures by definition(s).
        {--def[] : The picture definitions}
    ';
    
    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param PictureRepositoryInterface $pictureRepository
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function handle(
        InteractorInterface $io,
        PictureRepositoryInterface $pictureRepository,
    ): int {
        $definitions = $io->option(name: 'def');
        
        if (empty($definitions)) {
            $deletedNum = $pictureRepository->clear();
            $io->success(sprintf('Cleared all %d generated pictures', $deletedNum));
            return 0;
        }
                
        foreach($definitions as $definition) {
            if (!is_string($definition)) {
                continue;
            }
            
            $deletedPictures = $pictureRepository->deleteAll(definition: $definition);
            
            $io->success(sprintf('Cleared %d generated pictures for the definition %s', count($deletedPictures), $definition));
        }
        
        return 0;
    }
}