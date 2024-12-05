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

use Exception;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Tobento\App\Media\Queue\PictureQueueHandlerInterface;
use Tobento\App\Logging\LoggerTrait;
use Tobento\Service\Imager\InterventionImage\ImagerFactory;
use Tobento\Service\Imager\Resource;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\DefinitionsInterface;
use Tobento\Service\Picture\PictureCreator;
use Tobento\Service\Picture\PictureCreatorInterface;
use Tobento\Service\Picture\PictureTagInterface;
use Tobento\Service\Picture\PictureTag;
use Tobento\Service\Picture\NullPictureTag;
use Tobento\Service\Tag\Tag;
use Tobento\Service\Tag\Attributes;
use Throwable;

/**
 * The picture generator.
 */
class PictureGenerator implements PictureGeneratorInterface
{
    use LoggerTrait;
    
    /**
     * @var null|PictureCreatorInterface
     */
    protected null|PictureCreatorInterface $pictureCreator = null;
    
    /**
     * Create a new PictureGenerator instance.
     *
     * @param PictureRepositoryInterface $pictureRepository
     * @param StoragesInterface $storages
     * @param DefinitionsInterface $definitions
     * @param null|PictureQueueHandlerInterface $queueHandler
     * @param null|PictureCreatorInterface $pictureCreator
     */
    public function __construct(
        protected PictureRepositoryInterface $pictureRepository,
        protected StoragesInterface $storages,
        protected DefinitionsInterface $definitions,
        protected null|PictureQueueHandlerInterface $queueHandler = null,
        null|PictureCreatorInterface $pictureCreator = null,
    ) {
        $this->pictureCreator = $pictureCreator;
    }
    
    /**
     * Returns the picture repository.
     *
     * @return PictureRepositoryInterface
     */
    public function pictureRepository(): PictureRepositoryInterface
    {
        return $this->pictureRepository;
    }

    /**
     * Generate a new picture from the given path, resource and definition.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition A named definition or definition instance.
     * @param bool $queue
     * @return PictureTagInterface
     */
    public function generate(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $queue = true,
    ): PictureTagInterface {
        try {
            return $this->generating($path, $resource, $definition, $queue);
        } catch (Throwable $e) {
            $this->getLogger()->warning(
                message: sprintf('Generating picture for path %s failed: %s', $path, $e->getMessage()),
                context: ['exception' => $e]
            );
            return $this->createFallbackPictureTag($path, $resource, $definition);
        }
    }
    
    /**
     * Regenerate a new picture from the given path, resource and definition.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition A named definition or definition instance.
     * @param bool $queue
     * @return PictureTagInterface
     */
    public function regenerate(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $queue = true,
    ): PictureTagInterface {
        try {
            return $this->regenerating($path, $resource, $definition, $queue);
        } catch (Throwable $e) {
            $this->getLogger()->warning(
                message: sprintf('Regenerating picture for path %s failed: %s', $path, $e->getMessage()),
                context: ['exception' => $e]
            );
            return $this->createFallbackPictureTag($path, $resource, $definition);
        }
    }
    
    /**
     * Generating a new picture from the given path, resource and definition.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition A named definition or definition instance.
     * @param bool $queue
     * @return PictureTagInterface
     * @throws Throwable
     */
    protected function generating(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $queue = true,
    ): PictureTagInterface {
        // Resolve definition:
        if (is_string($definition)) {
            $definition = $this->definitions->get($definition);
        }
        
        // Check if picture is already generated:
        if ($picture = $this->pictureRepository->findOne($path, $definition)) {
            return $picture->toTag();
        }
        
        // Queue it if set:
        if ($this->queueHandler && $queue) {
            $this->queueHandler->handle(
                path: $path,
                resource: $resource,
                definition: $definition,
                regenerate: false,
                uniqueJob: true,
            );
            return $this->createFallbackPictureTag($path, $resource, $definition);
        }
        
        // Resolve resource:
        if (is_string($resource)) {
            $file = $this->storages->get($resource)->with('stream')->file(path: $path);
            $resource = new Resource\Stream($file->stream());
        }
        
        // Create and save picture:
        $createdPicture = $this->configurePictureCreator()->createFromResource(
            resource: $resource,
            definition: $definition,
        );

        return $this->pictureRepository->save($path, $definition, $createdPicture)->toTag();
    }
    
    /**
     * Regenerating a new picture from the given path, resource and definition.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition A named definition or definition instance.
     * @param bool $queue
     * @return PictureTagInterface
     * @throws Throwable
     */
    protected function regenerating(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $queue = true,
    ): PictureTagInterface {
        // Resolve definition:
        if (is_string($definition)) {
            $definition = $this->definitions->get($definition);
        }
        
        // Queue it if set:
        if ($this->queueHandler && $queue) {
            $this->queueHandler->handle(
                path: $path,
                resource: $resource,
                definition: $definition,
                regenerate: true,
                uniqueJob: false,
            );
            return $this->createFallbackPictureTag($path, $resource, $definition);
        }
        
        $this->pictureRepository->delete(path: $path, definition: $definition);
        
        return $this->generate($path, $resource, $definition, $queue);
    }
    
    /**
     * Returns the configured picture creator.
     *
     * @return PictureCreatorInterface
     */
    protected function configurePictureCreator(): PictureCreatorInterface
    {
        if ($this->pictureCreator) {
            return $this->pictureCreator;
        }

        return $this->pictureCreator = new PictureCreator(
            imager: (new ImagerFactory())->createImager(),
            upsize: null,
            skipSmallerSizedSrc: false,
            verifySizes: false,
            logger: $this->getLogger(),
        );
    }
    
    /**
     * Returns the created fallback picture tag.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition
     * @return PictureTagInterface
     */
    protected function createFallbackPictureTag(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
    ): PictureTagInterface {
        try {
            return $this->creatingFallbackPictureTag($path, $resource, $definition);
        } catch (Throwable $e) {
            $this->getLogger()->warning(
                message: sprintf('Creating fallback picture for path %s failed: %s', $path, $e->getMessage()),
                context: ['exception' => $e]
            );
            return new NullPictureTag();
        }
    }
    
    /**
     * Returns the created fallback picture tag.
     *
     * @param string $path A path such as 'foo/bar/image.jpg'
     * @param string|ResourceInterface $resource If string is provided it looks in file storage.
     * @param string|DefinitionInterface $definition
     * @return PictureTagInterface
     * @throws Throwable
     */
    protected function creatingFallbackPictureTag(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
    ): PictureTagInterface {
        switch ($resource) {
            case is_string($resource):
                $file = $this->storages->get($resource)->with('stream', 'url')->file(path: $path);

                if (empty($file->url())) {
                    if (is_null($file->stream())) {
                        throw new Exception('File stream is null');
                    }
                    
                    return $this->createFallbackPictureTag($path, new Resource\Stream($file->stream()), $definition);
                }

                $src = $file->url();
                $width = $file->width();
                $height = $file->height();
                break;
            case $resource instanceof Resource\Stream:
                $detector = new FinfoMimeTypeDetector();
                $mimeType = $detector->detectMimeTypeFromBuffer((string)$resource->stream());
                
                // verify mime type if it is an image:
                if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/gif'])) {
                    throw new Exception('Unsupported image type');
                }
                
                $base64 = base64_encode((string)$resource->stream());
                $src = sprintf('data:%s;base64,%s', (string)$mimeType, $base64);
                $size = getimagesizefromstring((string)$resource->stream());
                $width = $size[0] ?? 0;
                $height = $size[1] ?? 0;
                break;
            case $resource instanceof Resource\File:
                if (!in_array($resource->file()->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/gif'])) {
                    throw new Exception('Unsupported image type');
                }
                
                $base64 = base64_encode($resource->file()->getContent());
                $src = sprintf('data:%s;base64,%s', $resource->file()->getMimeType(), $base64);
                $width = $resource->file()->getImageSize(0);
                $height = $resource->file()->getImageSize(1);
                break;
            default:
                throw new Exception('Unsupported resource');
        }
        
        $attributes = new Attributes(['src' => $src]);
        
        if ($width > 0) {
            $attributes->set('width', (string)$width);
        }
        
        if ($height > 0) {
            $attributes->set('height', (string)$height);
        }
        
        return new PictureTag(
            new Tag(name: 'picture'),
            new Tag(name: 'img', attributes: $attributes),
        );
    }
}