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
 
namespace Tobento\App\Media\Icon;

use Tobento\App\Logging\LoggerTrait;
use Tobento\Service\Icon\IconFactoryInterface;
use Tobento\Service\Icon\IconInterface;
use Tobento\Service\Icon\IconNotFoundException;
use Tobento\Service\Icon\IconsInterface;

/**
 * FallbackIcons
 */
class FallbackIcons implements IconsInterface
{
    use LoggerTrait;
    
    /**
     * Create a new Icons.
     *
     * @param IconFactoryInterface $iconFactory
     */
    public function __construct(
        protected IconFactoryInterface $iconFactory,
        protected bool $throwIconNotFoundException = false,
    ) {
        
    }

    /**
     * Returns true if the specified icon exists, otherwise false.
     *
     * @param string $icon The icon name.
     * @return bool
     */
    public function has(string $icon): bool
    {
        return true;
    }
    
    /**
     * Returns the icon.
     *
     * @param string $icon The icon name.
     * @return IconInterface
     * @throws IconNotFoundException
     */
    public function get(string $icon): IconInterface
    {
        if ($this->throwIconNotFoundException) {
            throw new IconNotFoundException($icon);
        }
        
        $this->getLogger()->log(
            level: 'warning',
            message: sprintf('Icon %s not found', $icon),
        );
        
        return $this->iconFactory->createIcon(name: $icon);
    }
}