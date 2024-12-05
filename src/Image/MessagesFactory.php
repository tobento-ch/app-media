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
 
namespace Tobento\App\Media\Image;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tobento\Service\HelperFunction\Functions;
use Tobento\Service\Message\MessageFactoryInterface;
use Tobento\Service\Message\MessagesFactoryInterface;
use Tobento\Service\Message\ModifiersInterface;
use Tobento\Service\Imager\Message\MessagesFactory as ImagerMessagesFactory;

/**
 * MessagesFactory
 */
class MessagesFactory extends ImagerMessagesFactory
{
    /**
     * Create a new MessagesFactory.
     *
     * @param null|MessageFactoryInterface $messageFactory
     * @param null|ModifiersInterface $modifiers
     * @param null|LoggerInterface $logger
     */    
    public function __construct(
        protected null|MessageFactoryInterface $messageFactory = null,
        protected null|ModifiersInterface $modifiers = null,
        protected null|LoggerInterface $logger = null,
    ) {
        if (is_null($modifiers)) {
            $modifiers = $this->fetchModifiers();
        }
        
        parent::__construct($messageFactory, $modifiers, $logger);
    }
    
    /**
     * Returns the modifiers.
     *
     * @return null|ModifiersInterface
     */
    private function fetchModifiers(): null|ModifiersInterface
    {
        if (! Functions::has(ContainerInterface::class)) {
            return null;
        }
        
        $container = Functions::get(ContainerInterface::class);
        
        if ($container->has(MessagesFactoryInterface::class)) {
            return $container->get(MessagesFactoryInterface::class)->createMessages()->modifiers();
        }
        
        return null;
    }
}