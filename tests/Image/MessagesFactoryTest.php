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

namespace Tobento\App\Media\Test\Image;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Image\MessagesFactory;
use Tobento\Service\Container\Container;
use Tobento\Service\Imager\Action;
use Tobento\Service\Imager\Actions;
use Tobento\Service\Imager\Message\MessagesFactoryInterface;
use Tobento\Service\Message\MessagesFactoryInterface as ServiceMessagesFactoryInterface;
use Tobento\Service\Message\MessagesInterface;
use Tobento\Service\Message\Modifier;
use Tobento\Service\Message\Modifiers;
use Tobento\Service\HelperFunction\Functions;

class MessagesFactoryTest extends TestCase
{
    public function testThatImplementsInterface()
    {
        $this->assertInstanceof(
            MessagesFactoryInterface::class,
            new MessagesFactory()
        );
    }
    
    public function testCreateMessagesFromActions()
    {
        $actions = new Actions(
            new Action\Blur(blur: 20),
            new Action\Brightness(brightness: 20),
        );
                
        $messages = (new MessagesFactory())->createMessagesFromActions(actions: $actions);
        
        $this->assertInstanceof(MessagesInterface::class, $messages);
        $this->assertSame(2, count($messages->all()));
    }
    
    public function testCreateMessagesFromActionsIgnoresEmptyDescriptions()
    {
        $action = new class extends Action\Action {};
        
        $actions = new Actions(
            new Action\Blur(blur: 20),
            $action,
        );
                
        $messages = (new MessagesFactory())->createMessagesFromActions(actions: $actions);
        
        $this->assertSame(1, count($messages->all()));
    }
    
    public function testCreateMessagesFromActionsMessage()
    {
        $actions = new Actions(
            new Action\Blur(blur: 20),
        );
                
        $messages = (new MessagesFactory())->createMessagesFromActions(actions: $actions);
        
        $message = $messages->first();
        
        $this->assertSame('info', $message->level());
        $this->assertSame('Blured image by 20.', $message->message());
        
        $this->assertSame(
            ['action' => 'Tobento\Service\Imager\Action\Blur', 'processedByAction' => null],
            $messages->first()->context()
        );
        
        $this->assertSame(
            [':blur' => 20],
            $messages->first()->parameters()
        );
        
        $this->assertSame(null, $message->key());
        
        $this->assertFalse($message->logged());
    }
    
    public function testCreateMessagesFromActionsUsesModifiersFromContainer()
    {
        $container = new Container();
        $container->set(ServiceMessagesFactoryInterface::class, function () {
            return new MessagesFactory(
                modifiers: new Modifiers(
                    new Modifier\ParameterReplacer(),
                ),
            );
        });
        $functions = new Functions();
        $functions->set(ContainerInterface::class, $container);
        
        $actions = new Actions(
            new Action\Blur(blur: 20),
        );
                
        $messages = (new MessagesFactory())->createMessagesFromActions(actions: $actions);

        $this->assertSame(1, count($messages->modifiers()->all()));
    }
}