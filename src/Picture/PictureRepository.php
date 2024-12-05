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

namespace Tobento\App\Media\Picture;

use Tobento\App\Media\Exception\PictureException;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Picture\CreatedPictureInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\PictureFactory;
use Tobento\Service\Picture\PictureInterface;
use Throwable;

/**
 * PictureRepository
 */
class PictureRepository implements PictureRepositoryInterface
{
    /**
     * Create a new PictureRepository.
     *
     * @param string $pictureStorageName
     * @param string $imageStorageName
     * @param StoragesInterface $storages
     */
    public function __construct(
        protected string $pictureStorageName,
        protected string $imageStorageName,
        protected StoragesInterface $storages,
    ) {}
    
    /**
     * Returns the found picture by the given path and definition.
     *
     * @param string $path
     * @param string|DefinitionInterface $definition
     * @return null|PictureInterface
     */
    public function findOne(string $path, string|DefinitionInterface $definition): null|PictureInterface
    {
        $pictureStorage = $this->storages->get($this->pictureStorageName);
        
        $definitionName = is_string($definition) ? $definition : $definition->name();
        
        $picturePath = $this->buildPicturePath($path, $definitionName);
        
        if (! $pictureStorage->exists(path: $picturePath)) {
            return null;
        }

        $file = $pictureStorage->with('stream', 'mimeType')->file(path: $picturePath);

        $data = json_decode((string)$file->content(), true);

        $picture = (new PictureFactory())->createFromArray($data);
        return $picture;
    }
    
    /**
     * Saves the created picture and return the saved.
     *
     * @param string $path
     * @param DefinitionInterface $definition
     * @param CreatedPictureInterface $picture
     * @return PictureInterface
     * @throws PictureException
     */
    public function save(
        string $path,
        DefinitionInterface $definition,
        CreatedPictureInterface $picture
    ): PictureInterface {
        $fileStorage = $this->storages->get($this->imageStorageName);

        $savedPicture = (new SavedPictureFactory($fileStorage))->createFromCreated(
            path: $path,
            picture: $picture
        );
        
        $pictureStorage = $this->storages->get($this->pictureStorageName);
        
        $pictureStorage->write(
            path: $this->buildPicturePath($path, $definition->name()),
            content: json_encode($savedPicture->jsonSerialize()),
        );
        
        return $savedPicture;
    }
    
    /**
     * Deletes the created picture with all its created images.
     *
     * @param string $path
     * @param string|DefinitionInterface $definition
     * @return null|PictureInterface The deleted picture or null if none deleted.
     */
    public function delete(
        string $path,
        string|DefinitionInterface $definition,
    ): null|PictureInterface {
        if (is_null($picture = $this->findOne(path: $path, definition: $definition))) {
            return null;
        }
        
        // delete picture images:
        $this->deletePictureImages($picture);
        
        // delete picture:
        $pictureStorage = $this->storages->get($this->pictureStorageName);
        
        $definitionName = is_string($definition) ? $definition : $definition->name();
        
        $pictureStorage->delete($this->buildPicturePath($path, $definitionName));
        
        return $picture;
    }
    
    /**
     * Deletes all created pictures with all its created images.
     *
     * @param string $path
     * @param string|DefinitionInterface $definition
     * @return array<array-key, PictureInterface> The deleted picture.
     */
    public function deleteAll(
        string|DefinitionInterface $definition,
    ): array {
        $pictureStorage = $this->storages->get($this->pictureStorageName);
        
        $definitionName = is_string($definition) ? $definition : $definition->name();
        
        $files = $pictureStorage->with('stream', 'mimeType')->files(
            path: $this->buildFolderPath($definitionName),
            recursive: false // is default
        );
        
        $pictureFactory = new PictureFactory();
        $pictures = [];
        
        foreach($files as $file) {
            $data = json_decode((string)$file->content(), true);

            $picture = $pictureFactory->createFromArray($data);
            
            $this->deletePictureImages($picture);
            
            $pictureStorage->delete($file->path());
            
            $pictures[] = $picture;
        }
        
        return $pictures;
    }
    
    /**
     * Clear all created pictures.
     *
     * @return int The number of pictures cleared.
     */
    public function clear(): int
    {
        // Currently, clearing may take a long time if a lot of picture are created.
        // A better approach would be to store images in a folder. So we could just delete it.
        
        $pictureStorage = $this->storages->get($this->pictureStorageName);
        
        $files = $pictureStorage->with('stream', 'mimeType')->files(path: '', recursive: true);
        
        $pictureFactory = new PictureFactory();
        $cleared = 0;
        
        foreach($files as $file) {
            $data = json_decode((string)$file->content(), true);

            $picture = $pictureFactory->createFromArray($data);
            
            $this->deletePictureImages($picture);
            
            $pictureStorage->delete($file->path());
            $cleared++;
        }
        
        return $cleared;
    }
    
    /**
     * Delete picture images.
     *
     * @param PictureInterface $picture
     * @return null|PictureInterface
     */
    protected function deletePictureImages(PictureInterface $picture): null|PictureInterface
    {
        $storageName = $picture->options()['storage'] ?? $this->imageStorageName;
        
        if (! $this->storages->has($storageName)) {
            return null;
        }
        
        $fileStorage = $this->storages->get($storageName);
        
        // delete images:
        foreach($picture->srces() as $src) {
            $fileStorage->delete($src->path());
        }
        
        return $picture;
    }
    
    /**
     * Returns the build picture path.
     *
     * @param string $path
     * @param string $definition
     * @return string
     */
    protected function buildPicturePath(string $path, string $definitionName): string
    {
        $folderName = $this->buildFolderPath($definitionName);
        
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '-', trim($path));
        
        return sprintf('%s/%s.json', $folderName, $filename);
    }
    
    /**
     * Returns the build folder path.
     *
     * @param string $definition
     * @return string
     */
    protected function buildFolderPath(string $definitionName): string
    {
        $folderName = preg_replace('/[^A-Za-z0-9_\-]/', '-', trim($definitionName));
        return strtolower($folderName);
    }
}