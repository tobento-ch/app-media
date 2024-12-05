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

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Image\ImageActions;
use Tobento\App\Media\Image\ImageActionsInterface;
use Tobento\Service\Imager\Action;
use Tobento\Service\Imager\ActionCreateException;
use Tobento\Service\Imager\ActionInterface;

class ImageActionsTest extends TestCase
{
    public function testThatImplementsImageActionsInterface()
    {
        $this->assertInstanceof(ImageActionsInterface::class, new ImageActions());
    }
    
    public function testWithActionsMethod()
    {
        $actions = new ImageActions();
        $actionsNew = $actions->withActions(['crop']);
        
        $this->assertFalse($actions === $actionsNew);
        $this->assertSame(['crop'], $actionsNew->all());
    }
    
    public function testFiltersMethod()
    {
        $actions = new ImageActions();
        $actionsNew = $actions->filters();
        
        $this->assertFalse($actions === $actionsNew);
        $this->assertSame([], $actionsNew->all());
        
        $this->assertSame(
            ['greyscale', 'sepia'],
            (new ImageActions(['crop', 'greyscale', 'sepia']))->filters()->all()
        );
        
        $this->assertSame(
            ['crop'],
            (new ImageActions(['crop', 'greyscale', 'sepia']))->filters(false)->all()
        );
    }
    
    public function testAllMethod()
    {
        $this->assertSame([], (new ImageActions())->all());
        $this->assertSame(['crop', 'sepia'], (new ImageActions(['crop', 'sepia']))->all());
    }
    
    public function testHasMethod()
    {
        $actions = new ImageActions(['crop', 'sepia']);
        
        $this->assertTrue($actions->has('crop'));
        $this->assertTrue($actions->has('sepia'));
        $this->assertFalse($actions->has('greyscale'));
    }
    
    public function testGetAllowedActionsMethod()
    {
        $this->assertSame([], (new ImageActions())->getAllowedActions());
        
        $this->assertSame(
            [
                Action\Crop::class,
            ],
            (new ImageActions(['crop']))->getAllowedActions()
        );
    }
    
    public function testVerifyInputActionsMethod()
    {
        $actions = new ImageActions();
        $this->assertSame([], $actions->verifyInputActions('invalid'));
        $this->assertSame([], $actions->verifyInputActions(34));
        $this->assertSame([], $actions->verifyInputActions([]));
        $this->assertSame([], $actions->verifyInputActions(['crop']));
        $this->assertSame(['sepia' => []], $actions->verifyInputActions(['sepia' => []]));
        $this->assertSame(['sepia' => []], $actions->verifyInputActions(['sepia' => '']));
        $this->assertSame(['sepia' => []], $actions->verifyInputActions(['sepia' => 12]));
        $this->assertSame([], $actions->verifyInputActions(['gamma' => ['gamma']]));
        $this->assertSame(['gamma' => ['gamma' => 5.5]], $actions->verifyInputActions(['gamma' => ['gamma' => '5.5']]));
        $this->assertSame(['gamma' => ['gamma' => 5.5]], $actions->verifyInputActions(['gamma' => ['gamma' => 5.5]]));
        $this->assertSame([], $actions->verifyInputActions(['gamma' => ['gamma' => 'abc']]));
        $this->assertSame([], $actions->verifyInputActions(['gamma' => ['gamma' => ['gamma' => '5.5', 'foo' => '3']]]));
        
        $this->assertSame(
            ['resize' => ['width' => 20, 'height' => null, 'keepRatio' => true, 'upsize' => null, 'srcWidth' => null, 'srcHeight' => null]],
            $actions->verifyInputActions(['resize' => ['width' => '20', 'keepRatio' => 'true']])
        );
        
        $this->assertSame(
            ['resize' => ['width' => 20, 'height' => null, 'keepRatio' => false, 'upsize' => null, 'srcWidth' => null, 'srcHeight' => null]],
            $actions->verifyInputActions(['resize' => ['width' => '20', 'keepRatio' => 'false']])
        );
        
        $this->assertSame(
            ['crop' => ['width' => 20, 'height' => 40, 'x' => null, 'y' => null]],
            $actions->verifyInputActions(['crop' => ['width' => '20', 'height' => 40, 'scale' => 0.4]])
        );
    }
    
    public function testVerifyInputActionsMethodLogsIfActionCreationFails()
    {
        $logger = new Logger('name');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);
        
        $actions = new ImageActions();
        $actions->setLogger($logger);
        $verified = $actions->verifyInputActions(['gamma' => ['gamma' => 'abc']]);
        
        $this->assertTrue($testHandler->hasRecord('Unable to create input action gamma', 'notice'));
    }    
    
    public function testCreateActionMethod()
    {
        $actions = [
            'background' => ['color' => '#333'],
            'blur' => ['blur' => 20],
            'brightness' => ['brightness' => 20],
            'colorize' => ['red' => 10, 'green' => '10', 'blue' => 10],
            'contrast' => ['contrast' => 20],
            'crop' => ['width' => 50, 'height' => 50],
            'encode' => ['mimeType' => 'image/webp'],
            'fit' => ['width' => 50, 'height' => 50],
            'flip' => [],
            'gamma' => ['gamma' => 5.5],
            'gamma' => ['gamma' => '5.5'],
            'greyscale' => [],
            'orientate' => [],
            'pixelate' => ['pixelate' => 10],
            'resize' => ['width' => 50],
            'rotate' => ['degrees' => 50],
            'save' => ['filename' => '/image.jpg'],
            'sepia' => [],
            'sharpen' => ['sharpen' => 10],
        ];
        
        $ia = new ImageActions();
        
        foreach($actions as $name => $params) {
            $this->assertInstanceof(
                ActionInterface::class,
                $ia->createAction($name, $params)
            );
        }
    }
    
    public function testCreateActionMethodThrowsActionCreateExceptionIfActionNotFound()
    {
        $this->expectException(ActionCreateException::class);

        (new ImageActions())->createAction('foo', []);
    }
    
    public function testCreateActionMethodThrowsActionCreateExceptionIfInvalidParams()
    {
        $this->expectException(ActionCreateException::class);

        (new ImageActions())->createAction('background', ['foo' => '']);
    }
}