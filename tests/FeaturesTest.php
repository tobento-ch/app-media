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

namespace Tobento\App\Media\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Boot;
use Tobento\App\Media\FeatureInterface;
use Tobento\App\Media\Features;
use Tobento\App\Media\FeaturesInterface;

class FeaturesTest extends TestCase
{
    public function createFeature(string $name): FeatureInterface
    {
        return new class($name) extends Boot implements FeatureInterface
        {
            public function __construct(
                private string $name
            ) {}

            public function featureName(): string
            {
                return $this->name;
            }

            public function featureGroup(): string
            {
                return $this->name;
            }
        };
    }
    
    public function testThatImplementsFeaturesInterface()
    {
        $this->assertInstanceof(FeaturesInterface::class, new Features());
    }
    
    public function testAddMethod()
    {
        $features = new Features();
        $features->add($this->createFeature(name: 'image'));
        
        $this->assertTrue($features->has('image'));
    }
    
    public function testHasMethod()
    {
        $features = new Features();
        $features->add($this->createFeature(name: 'image'));
        
        $this->assertTrue($features->has('image'));
        $this->assertFalse($features->has('icons'));
    }
    
    public function testGetMethod()
    {
        $feature = $this->createFeature(name: 'image');
        $features = new Features();
        $features->add($feature);
        
        $this->assertSame($feature, $features->get('image'));
        $this->assertSame(null, $features->get('icons'));
    }
    
    public function testAllMethod()
    {
        $feature = $this->createFeature(name: 'image');
        $features = new Features();
        
        $this->assertSame([], $features->all());
        
        $features->add($feature);
        
        $this->assertSame(['image' => $feature], $features->all());
    }
}