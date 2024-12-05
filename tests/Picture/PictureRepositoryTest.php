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
use Tobento\App\Media\Picture\PictureRepository;
use Tobento\App\Media\Picture\PictureRepositoryInterface;
use Tobento\App\Media\Test\Factory;
use Tobento\Service\Imager\InterventionImage\ImagerFactory;
use Tobento\Service\Imager\Resource\File;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Picture\Definition\ArrayDefinition;
use Tobento\Service\Picture\PictureCreator;
use Tobento\Service\Picture\PictureCreatorInterface;
use Tobento\Service\Picture\PictureInterface;

class PictureRepositoryTest extends TestCase
{
    public function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/picture-storage/');
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/image-storage/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/picture-storage/');
        (new Dir())->delete(__DIR__.'/../tmp/file-storage/image-storage/');
    }
    
    protected function createPictureRepository(StoragesInterface $storages): PictureRepositoryInterface
    {
        return new PictureRepository(
            pictureStorageName: 'picture-storage',
            imageStorageName: 'image-storage',
            storages: $storages,
        );
    }
    
    protected function createFileStorages(): StoragesInterface
    {
        $storages = Factory::createFileStorages(['picture-storage'], withPublicUrl: false);
        $storages->add(Factory::createFileStorage('image-storage', withPublicUrl: true));
        return $storages;
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
    
    public function testFindOneMethodReturnsNullIfNotExists()
    {
        $picture = $this->createPictureRepository($this->createFileStorages())
            ->findOne(path: 'image.jpg', definition: 'product-main');
        
        $this->assertSame(null, $picture);
    }
    
    public function testFindOneMethodReturnsPictureIfExists()
    {
        $definition = new ArrayDefinition('product-main', [
            'img' => ['src' => [30]],
        ]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $repo = $this->createPictureRepository($this->createFileStorages());
        $repo->save(
            path: 'image.jpg',
            definition: $definition,
            picture: $createdPicture
        );
        
        $picture = $repo->findOne(path: 'image.jpg', definition: 'product-main');
        
        $this->assertInstanceof(PictureInterface::class, $picture);
    }
    
    public function testFindOneMethodWithDefinitionObjectReturnsPictureIfExists()
    {
        $definition = new ArrayDefinition('product-main', [
            'img' => ['src' => [30]],
        ]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $repo = $this->createPictureRepository($this->createFileStorages());
        $repo->save(
            path: 'foo/bar/image.jpg',
            definition: $definition,
            picture: $createdPicture
        );
        
        $picture = $repo->findOne(path: 'foo/bar/image.jpg', definition: $definition);
        
        $this->assertInstanceof(PictureInterface::class, $picture);
    }
    
    public function testSaveMethod()
    {
        $definition = new ArrayDefinition('product-main', [
            'img' => ['src' => [30]],
        ]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        
        $this->assertSame(0, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(0, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(
            path: 'image.jpg',
            definition: $definition,
            picture: $createdPicture
        );
        
        $this->assertSame(1, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(1, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertFalse($createdPicture === $picture);
        $this->assertNotNull($picture->img()->src()->url());
        $this->assertInstanceof(PictureInterface::class, $picture);
    }
    
    public function testSaveMethodSavingWithSamePathButDifferentDefinition()
    {
        $definition = new ArrayDefinition('product-main', ['img' => ['src' => [30]]]);
        $definitionList = new ArrayDefinition('product-list', ['img' => ['src' => [30]]]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        
        $this->assertSame(0, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(0, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(path: 'foo/image.jpg', definition: $definition, picture: $createdPicture);
        $picture = $repo->save(path: 'foo/image.jpg', definition: $definitionList, picture: $createdPicture);
        
        $this->assertSame(2, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(2, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertFalse($createdPicture === $picture);
        $this->assertNotNull($picture->img()->src()->url());
        $this->assertInstanceof(PictureInterface::class, $picture);
    }    
    
    public function testDeleteMethod()
    {
        $definition = new ArrayDefinition('product-main', ['img' => ['src' => [30]]]);
        $definitionList = new ArrayDefinition('product-list', ['img' => ['src' => [30]]]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(path: 'image.jpg', definition: $definition, picture: $createdPicture);
        $picture = $repo->save(path: 'image.jpg', definition: $definitionList, picture: $createdPicture);
        
        $this->assertSame(2, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(2, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        
        $deletedPicture = $repo->delete(path: 'image.jpg', definition: $definition);
        
        $this->assertSame(1, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(1, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertNotNull($picture->img()->src()->url());
        $this->assertInstanceof(PictureInterface::class, $deletedPicture);
    }
    
    public function testDeleteMethodWithStringDefinition()
    {
        $definition = new ArrayDefinition('product-main', ['img' => ['src' => [30]]]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(path: 'image.jpg', definition: $definition, picture: $createdPicture);
        
        $this->assertSame(1, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(1, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        
        $deletedPicture = $repo->delete(path: 'image.jpg', definition: 'product-main');
        
        $this->assertSame(0, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(0, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertNotNull($deletedPicture->img()->src()->url());
        $this->assertInstanceof(PictureInterface::class, $deletedPicture);
    }
    
    public function testDeleteMethodWithNotFoundDefinitionReturnsNull()
    {
        $definition = new ArrayDefinition('product-main', ['img' => ['src' => [30]]]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(path: 'image.jpg', definition: $definition, picture: $createdPicture);
        
        $deletedPicture = $repo->delete(path: 'image.jpg', definition: 'product-list');
        
        $this->assertSame(1, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(1, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertNull($deletedPicture);
    }
    
    public function testDeleteAllMethod()
    {
        $definition = new ArrayDefinition('product-main', ['img' => ['src' => [30]]]);
        $definitionList = new ArrayDefinition('product-list', ['img' => ['src' => [30]]]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(path: 'image.jpg', definition: $definition, picture: $createdPicture);
        $picture = $repo->save(path: 'foo/image.jpg', definition: $definition, picture: $createdPicture);
        $picture = $repo->save(path: 'image.jpg', definition: $definitionList, picture: $createdPicture);
        
        $this->assertSame(3, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(3, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        
        $deletedPictures = $repo->deleteAll(definition: $definition);
        
        $this->assertSame(1, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(1, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertNotNull($deletedPictures[0]->img()->src()->url());
        $this->assertSame(2, count($deletedPictures));
    }
    
    public function testDeleteAllMethodWithStringDefinition()
    {
        $definition = new ArrayDefinition('product-main', ['img' => ['src' => [30]]]);
        $definitionList = new ArrayDefinition('product-list', ['img' => ['src' => [30]]]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(path: 'image.jpg', definition: $definition, picture: $createdPicture);
        $picture = $repo->save(path: 'foo/image.jpg', definition: $definition, picture: $createdPicture);
        $picture = $repo->save(path: 'image.jpg', definition: $definitionList, picture: $createdPicture);
        
        $this->assertSame(3, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(3, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        
        $deletedPictures = $repo->deleteAll(definition: 'product-main');
        
        $this->assertSame(1, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(1, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertNotNull($deletedPictures[0]->img()->src()->url());
        $this->assertSame(2, count($deletedPictures));
    }
    
    public function testDeleteAllMethodWithNotFoundDefinition()
    {
        $definition = new ArrayDefinition('product-main', ['img' => ['src' => [30]]]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(path: 'image.jpg', definition: $definition, picture: $createdPicture);
        
        $this->assertSame(1, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(1, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        
        $deletedPictures = $repo->deleteAll(definition: 'product-unkonwn');
        
        $this->assertSame(1, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(1, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(0, count($deletedPictures));
    }
    
    public function testClearMethod()
    {
        $definition = new ArrayDefinition('product-main', ['img' => ['src' => [30]]]);
        $definitionList = new ArrayDefinition('product-list', ['img' => ['src' => [30]]]);
        
        $createdPicture = $this->createPictureCreator()->createFromResource(
            resource: new File(__DIR__.'/../tmp/app/storage/uploads/image.jpg'),
            definition: $definition,
        );
        
        $storages = $this->createFileStorages();
        $repo = $this->createPictureRepository($storages);
        $picture = $repo->save(path: 'image.jpg', definition: $definition, picture: $createdPicture);
        $picture = $repo->save(path: 'image.jpg', definition: $definitionList, picture: $createdPicture);
        
        $this->assertSame(2, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(2, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        
        $deletedPictures = $repo->clear();
        
        $this->assertSame(0, count($storages->get('picture-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(0, count($storages->get('image-storage')->files(path: '', recursive: true)->all()));
        $this->assertSame(2, $deletedPictures);
    }    
}