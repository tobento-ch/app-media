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

use Tobento\Service\Imager\ActionFactoryInterface;

/**
 * ImageActionsInterface
 */
interface ImageActionsInterface extends ActionFactoryInterface
{
    /**
     * Returns a new instance with the specified allowed actions.
     *
     * @param array<array-key, string> The allowed actions such as ['crop', 'resize']
     * @return static
     */
    public function withActions(array $actions): static;
    
    /**
     * Returns a new instance with filters or non filters actions.
     *
     * @param bool $filters
     * @return static
     */
    public function filters(bool $filters = true): static;
    
    /**
     * Returns all actions.
     *
     * @return array<array-key, string>
     */
    public function all(): array;
    
    /**
     * Returns true if action exists, otherwise false.
     *
     * @return bool
     */
    public function has(string $action): bool;
    
    /**
     * Returns the allowed actions.
     *
     * @return array<array-key, class-string>
     */
    public function getAllowedActions(): array;
    
    /**
     * Returns the verified input actions.
     *
     * @param mixed $actions
     * @return array
     */
    public function verifyInputActions(mixed $actions): array;
}