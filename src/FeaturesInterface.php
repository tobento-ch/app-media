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
 
namespace Tobento\App\Media;

/**
 * FeaturesInterface
 */
interface FeaturesInterface
{
    /**
     * Adds a feature.
     *
     * @param FeatureInterface $feature
     * @return static $this
     */
    public function add(FeatureInterface $feature): static;
    
    /**
     * Determines if a feature exists.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;
    
    /**
     * Returns a feature by name if exists, otherwise null.
     *
     * @param string $name
     * @return null|FeatureInterface
     */
    public function get(string $name): null|FeatureInterface;
    
    /**
     * Returns all features.
     *
     * @return array<string, FeatureInterface>
     */
    public function all(): array;
}