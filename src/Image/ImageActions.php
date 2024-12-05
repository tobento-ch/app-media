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

use Tobento\App\Logging\LoggerTrait;
use Tobento\Service\Imager\Action;
use Tobento\Service\Imager\ActionCreateException;
use Tobento\Service\Imager\ActionInterface;
use Throwable;

/**
 * ImageActions
 */
class ImageActions implements ImageActionsInterface
{
    use LoggerTrait;
    
    /**
     * @var array<array-key, string>
     */
    protected array $actions = [];
    
    /**
     * @var array<array-key, class-string>
     */
    protected array $allowedActions = [];
    
    /**
     * Create a new ImageActions instance.
     *
     * @param array<array-key, string> $actions The allowed actions such as ['crop', 'resize']
     */
    final public function __construct(
        array $actions = [],
    ) {
        foreach($actions as $action) {
            if (in_array($action, ['quality', 'format'])) {
                $this->actions[] = $action;
                continue;
            }
            
            if (!is_string($action)) {
                continue;
            }
            
            $class = $this->getAvailableActions()[$action] ?? null;
            
            if ($class) {
                $this->actions[] = $action;
                $this->allowedActions[] = $class;
            }
        }
    }

    /**
     * Returns a new instance with the specified allowed actions.
     *
     * @param array<array-key, string> The allowed actions such as ['crop', 'resize']
     * @return static
     */
    public function withActions(array $actions): static
    {
        return new static($actions);
    }

    /**
     * Returns a new instance with filters or non filters actions.
     *
     * @param bool $filters
     * @return static
     */
    public function filters(bool $filters = true): static
    {
        $actions = [];
        
        if ($filters) {
            foreach($this->all() as $action) {
                if (in_array($action, $this->getFilterActions())) {
                    $actions[] = $action;
                }
            }
            
            return new static($actions);
        }

        foreach($this->all() as $action) {
            if (!in_array($action, $this->getFilterActions())) {
                $actions[] = $action;
            }
        }
        
        return new static($actions);
    }
    
    /**
     * Returns all actions.
     *
     * @return array<array-key, string>
     */
    public function all(): array
    {
        return $this->actions;
    }
    
    /**
     * Returns true if action exists, otherwise false.
     *
     * @return bool
     */
    public function has(string $action): bool
    {
        return in_array($action, $this->actions);
    }
    
    /**
     * Returns the allowed actions.
     *
     * @return array<array-key, class-string>
     */
    public function getAllowedActions(): array
    {
        return $this->allowedActions;
    }
    
    /**
     * Returns the verified input actions.
     *
     * @param mixed $actions
     * @return array
     */
    public function verifyInputActions(mixed $actions): array
    {
        if (!is_array($actions)) {
            return [];
        }

        $verifiedActions = [];
        
        foreach($actions as $name => $parameters) {
            if (!is_string($name)) {
                continue;
            }
            
            if (!is_array($parameters)) {
                $parameters = [];
            }
            
            try {
                $action = $this->createAction($name, $parameters);
                $verifiedActions[$name] = $action->parameters();
            } catch (ActionCreateException $e) {
                // ignore exception but we log:
                $this->getLogger()->log(
                    'notice',
                    sprintf('Unable to create input action %s', $name),
                    ['exception' => $e]
                );
            }
        }

        return $verifiedActions;
    }
    
    /**
     * Create a new action.
     *
     * @param string $name
     * @param array $parameters
     * @return ActionInterface
     * @throws ActionCreateException
     */
    public function createAction(string $name, array $parameters): ActionInterface
    {
        try {            
            $class = $this->getAvailableActions()[$name] ?? null;
            
            if (is_null($class)) {
                throw new ActionCreateException('Action ['.$name.'] not found');
            }
            
            $action = new $class(...$this->verifyParameters($parameters, $class));
            
            if (!$action instanceof ActionInterface) {
                throw new ActionCreateException(
                    'Action must be an instanceof '.ActionInterface::class
                );
            }
            
            return $action;
        } catch (Throwable $e) {
            throw new ActionCreateException('Could not create action ['.$name.']', 0, $e);
        }
    }

    /**
     * Returns the verified parameters.
     *
     * @param array $parameters
     * @param string $class
     * @return array
     */
    protected function verifyParameters(array $parameters, string $class): array
    {
        $verified = [];
        
        foreach($parameters as $name => $value) {
            if (is_numeric($value)) {
                $value = str_contains((string)$value, '.') ? floatval($value) : intval($value);
            } elseif ($value === 'false') {
                $value = false;
            } elseif ($value === 'true') {
                $value = true;
            }
            
            if ($class === Action\Crop::class && $name === 'scale') {
                continue;
            }
            
            $verified[$name] = $value;
        }
        
        return $verified;
    }
    
    /**
     * Returns the filter actions.
     *
     * @return array<array-key, string>
     */
    protected function getFilterActions(): array
    {
        return ['greyscale', 'sepia'];
    }
    
    /**
     * Returns the available actions.
     *
     * @return array<string, class-string>
     */
    protected function getAvailableActions(): array
    {
        return [
            'background' => Action\Background::class,
            'blur' => Action\Blur::class,
            'brightness' => Action\Brightness::class,
            'colorize' => Action\Colorize::class,
            'contrast' => Action\Contrast::class,
            'crop' => Action\Crop::class,
            'encode' => Action\Encode::class,
            'fit' => Action\Fit::class,
            'flip' => Action\Flip::class,
            'gamma' => Action\Gamma::class,
            'greyscale' => Action\Greyscale::class,
            'orientate' => Action\Orientate::class,
            'pixelate' => Action\Pixelate::class,
            'resize' => Action\Resize::class,
            'rotate' => Action\Rotate::class,
            'save' => Action\Save::class,
            'sepia' => Action\Sepia::class,
            'sharpen' => Action\Sharpen::class,
        ];
    }
}