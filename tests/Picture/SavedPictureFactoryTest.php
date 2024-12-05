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

namespace Tobento\App\Media\Test\Picture;

use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Picture\SavedPictureFactory;
use Tobento\App\Media\Picture\SavedPictureFactoryInterface;
use Tobento\App\Media\Picture\SavedPictureInterface;
use Tobento\App\Media\Test\Factory;
use Tobento\Service\Imager\InterventionImage\ImagerFactory;
use Tobento\Service\Imager\Resource\File;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Picture\Definition\ArrayDefinition;
use Tobento\Service\Picture\PictureCreator;
use Tobento\Service\Picture\PictureCreatorInterface;

class SavedPictureFactoryTest extends TestCase
{
    public function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/image-storage/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/image-storage/');
    }
    
    protected function createFileStorage(bool $withPublicUrl = true): StorageInterface
    {
        return Factory::createFileStorage('image-storage', withPublicUrl: $withPublicUrl);
    }
    
    protected function createPictureCreator(): PictureCreatorInterface
    {
        return new PictureCreator(
            imager: (new ImagerFactory())->createImager(),
            upsize: null,
            skipSmallerSizedSrc: false,
            verifySizes: false,
        );
    }
    
    public function testCreateFromCreatedMethodSavesImgSrcOnly()
    {
        $storage = $this->createFileStorage();
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: new ArrayDefinition('product', [
                'img' => [
                    'src' => [30, 15],
                ],
            ])
        );
        
        $savedPicture = (new SavedPictureFactory($storage))->createFromCreated(
            path: 'image.jpg',
            picture: $createdPicture
        );
        
        $this->assertSame(1, count($storage->files(path: '', recursive: true)->all()));
        $src = $savedPicture->img()->src();
        $this->assertTrue(str_contains($src->path(), 'image-30x15'));
        $this->assertTrue(str_ends_with($src->path(), '.jpg'));
        $this->assertSame(['storage' => 'image-storage'], $src->options());
        $this->assertSame(30, $src->width());
        $this->assertSame(15, $src->height());
        $this->assertSame('image/jpeg', $src->mimeType());
        $this->assertTrue(str_starts_with($src->url(), 'https://www.example.com/files/image-storage/image-30x15'));
        $this->assertTrue(str_ends_with($src->url(), '.jpg'));
        $this->assertSame(null, $src->encoded());
        $this->assertSame([], $savedPicture->img()->attributes());
        $this->assertSame(null, $savedPicture->img()->srcset());
        $this->assertSame(0, count($savedPicture->sources()));
    }
    
    public function testCreateFromCreatedMethodSavesImgSrcWithSrcset()
    {
        $storage = $this->createFileStorage();
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: new ArrayDefinition('product', [
                'img' => [
                    'src' => [30, 15],
                    'sizes' => '(max-width: 600px) 18px, 13px',
                    'srcset' => [
                        '18w' => [18, 17],
                        '13w' => [13, 12],
                    ],
                ],
            ])
        );
        
        $savedPicture = (new SavedPictureFactory($storage))->createFromCreated(
            path: 'image.jpg',
            picture: $createdPicture
        );
                
        $this->assertSame(3, count($storage->files(path: '', recursive: true)->all()));
        $this->assertSame(2, $savedPicture->img()->srcset()->count());
        $src = $savedPicture->img()->srcset()->all()[0];
        $this->assertTrue(str_contains($src->path(), 'image-18x17'));
        $this->assertTrue(str_ends_with($src->path(), '.jpg'));
        $this->assertSame(['storage' => 'image-storage'], $src->options());
        $this->assertSame(18, $src->width());
        $this->assertSame(17, $src->height());
        $this->assertSame('image/jpeg', $src->mimeType());
        $this->assertTrue(str_starts_with($src->url(), 'https://www.example.com/files/image-storage/image-18x17'));
        $this->assertTrue(str_ends_with($src->url(), '.jpg'));
        $this->assertSame(null, $src->encoded());
        $this->assertSame(['sizes' => '(max-width: 600px) 18px, 13px'], $savedPicture->img()->attributes());
        $this->assertSame(0, count($savedPicture->sources()));
    }
    
    public function testCreateFromCreatedMethodSavesSources()
    {
        $storage = $this->createFileStorage();
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: new ArrayDefinition('product', [
                'img' => [
                    'src' => [30, 15],
                ],
                'sources' => [
                    [
                        'media' => '(min-width: 800px)',
                        'srcset' => [
                            '' => [12, 11],
                        ],
                        'type' => 'image/gif',
                    ],
                ],
            ])
        );
        
        $savedPicture = (new SavedPictureFactory($storage))->createFromCreated(
            path: 'image.jpg',
            picture: $createdPicture
        );
                
        $this->assertSame(2, count($storage->files(path: '', recursive: true)->all()));
        $this->assertSame(1, $savedPicture->sources()->count());
        $src = $savedPicture->sources()->all()[0]->srcset()->all()[0];
        $this->assertTrue(str_contains($src->path(), 'image-12x11'));
        $this->assertTrue(str_ends_with($src->path(), '.gif'));
        $this->assertSame(['storage' => 'image-storage'], $src->options());
        $this->assertSame(12, $src->width());
        $this->assertSame(11, $src->height());
        $this->assertSame('image/gif', $src->mimeType());
        $this->assertTrue(str_starts_with($src->url(), 'https://www.example.com/files/image-storage/image-12x11'));
        $this->assertTrue(str_ends_with($src->url(), '.gif'));
        $this->assertSame(null, $src->encoded());
        $this->assertSame(
            ['media' => '(min-width: 800px)', 'type' => 'image/gif'],
            $savedPicture->sources()->all()[0]->attributes()
        );
    }
    
    public function testCreateFromCreatedMethodOptionsAndAttributesAreSaved()
    {
        $storage = $this->createFileStorage();
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: new ArrayDefinition('product', [
                'img' => [
                    'src' => [30, 15],
                ],
                'attributes' => ['class' => 'foo'],
                'options' => ['key' => 'value'],
            ])
        );
        
        $savedPicture = (new SavedPictureFactory($storage))->createFromCreated(
            path: 'image.jpg',
            picture: $createdPicture
        );
        
        $this->assertSame(['class' => 'foo'], $savedPicture->attributes());
        $this->assertSame(['key' => 'value'], $savedPicture->options());
    }
}