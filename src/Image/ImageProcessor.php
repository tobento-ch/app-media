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

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\StreamInterface;
use Tobento\App\Media\Exception\ImageProcessException;
use Tobento\App\Media\Exception\UnsupportedImageException;
use Tobento\App\Logging\LoggerTrait;
use Tobento\Service\Imager\InterventionImage\ImagerFactory;
use Tobento\Service\Imager\ImageFormats;
use Tobento\Service\Imager\ActionInterface;
use Tobento\Service\Imager\Action;
use Tobento\Service\Imager\ActionCreateException;
use Tobento\Service\Imager\ActionFactoryInterface;
use Tobento\Service\Imager\ActionFactory;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Imager\Resource\Stream;
use Tobento\Service\Imager\Resource\File;
use Tobento\Service\Imager\Response\Encoded;
use Tobento\Service\Imager\ImagerException;

/**
 * ImagerProcessor
 */
class ImageProcessor implements ImageProcessorInterface
{
    use LoggerTrait;
    
    /**
     * @var ActionFactoryInterface
     */
    protected ActionFactoryInterface $actionFactory;
    
    /**
     * @var null|array<array-key, ActionInterface>
     */
    protected null|array $createdActions = null;
    
    /**
     * Create a new ImageProcessor.
     *
     * @param array $actions The actions to process ['resize' => ['width' => 300], new Action\Resize(width: 300)]
     * @param array $allowedActions [Action\Crop::class] If empty array all are allowed if not in disallowed actions.
     * @param array $disallowedActions [Action\Resize::class]
     * @param array $convert ['image/png' => 'image/jpeg']
     * @param array $quality ['image/jpeg' => 90]
     * @param array $supportedMimeTypes ['image/png', 'image/jpeg']
     * @param null|ActionFactoryInterface $actionFactory
     */
    public function __construct(
        protected array $actions = [],
        protected array $allowedActions = [],
        protected array $disallowedActions = [],
        protected array $convert = [],
        protected array $quality = [],
        protected array $supportedMimeTypes = ['image/png', 'image/jpeg', 'image/gif'],
        null|ActionFactoryInterface $actionFactory = null,
    ) {
        $this->actionFactory = $actionFactory ?: new ActionFactory();
    }

    /**
     * Returns a new instance with the specified actions to process.
     *
     * @param array $actions ['resize' => ['width' => 300], new Action\Resize(width: 300)]
     * @return static
     */
    public function withActions(array $actions): static
    {
        $new = clone $this;
        $new->actions = $actions;
        return $new;
    }

    /**
     * Returns a new instance with the specified convert to process.
     *
     * @param array $convert ['image/png' => 'image/jpeg']
     * @return static
     */
    public function withConvert(array $convert): static
    {
        $new = clone $this;
        $new->convert = $convert;
        return $new;
    }
    
    /**
     * Returns a new instance with the specified quality to process.
     *
     * @param array $quality ['image/jpeg' => 90]
     * @return static
     */
    public function withQuality(array $quality): static
    {
        $new = clone $this;
        $new->quality = $quality;
        return $new;
    }
    
    /**
     * Process image from resource.
     *
     * @param ResourceInterface $resource
     * @return Encoded
     * @throws ImageProcessException
     */
    public function processFromResource(ResourceInterface $resource): Encoded
    {
        return $this->processImage($resource);
    }
    
    /**
     * Process image from stream.
     *
     * @param StreamInterface $stream
     * @return Encoded
     * @throws ImageProcessException
     */
    public function processFromStream(StreamInterface $stream): Encoded
    {
        return $this->processImage(new Stream($stream));
    }

    /**
     * Process image.
     *
     * @param ResourceInterface $resource
     * @return Encoded
     * @throws ImageProcessException
     */
    protected function processImage(ResourceInterface $resource): Encoded
    {
        $mimeType = $this->verifyMimeType($resource);
        
        // Convert and verify mime types:
        if (
            isset($this->convert[$mimeType])
            && $this->isSupportedMimeType($this->convert[$mimeType]))
        {
            $mimeType = $this->convert[$mimeType];
        }
        
        // Verify quality:
        $quality = $this->getVerifiedQuality($mimeType);
        
        // Process:
        try {
            $imager = (new ImagerFactory())->createImager();
            $imager = $imager->resource($resource);
            
            foreach($this->createActions() as $action) {
                if (in_array($action::class, $this->disallowedActions())) {
                    $this->getLogger()->notice(sprintf('Disallowed action %s skipped', $action::class));
                    continue;
                }
                
                if (!empty($this->allowedActions()) && !in_array($action::class, $this->allowedActions())) {
                    $this->getLogger()->notice(sprintf('Disallowed action %s skipped', $action::class));
                    continue;
                }
                
                $imager = $imager->action($action);
            }
            
            return $imager->action(new Action\Encode(mimeType: $mimeType, quality: $quality));
        } catch (ImagerException $e) {
            throw new ImageProcessException(
                resource: $resource,
                message: $e->getMessage(),
                code: (int)$e->getCode(),
                previous: $e,
            );
        }
    }
    
    /**
     * Returns the verified quality for the given mime type.
     *
     * @param string $mimeType
     * @return null|int
     */
    protected function getVerifiedQuality(string $mimeType): null|int
    {
        $quality = $this->quality[$mimeType] ?? null;
        
        return is_numeric($quality) ? (int)$quality : null;
    }

    /**
     * Returns the allowed actions.
     *
     * @return array<array-key, class-string>
     */
    protected function allowedActions(): array
    {
        return $this->allowedActions;
    }
    
    /**
     * Returns the disallowed actions.
     *
     * @return array<array-key, class-string>
     */
    protected function disallowedActions(): array
    {
        // never allow response actions:
        $disallowed = [
            Action\Save::class,
            Action\Encode::class,
        ];
        
        return array_merge($this->disallowedActions, $disallowed);
    }
    
    /**
     * Returns the created actions.
     *
     * @param string $path
     * @return array<array-key, ActionInterface>
     */
    protected function createActions(): array
    {
        if (is_array($this->createdActions)) {
            return $this->createdActions;
        }
        
        $actions = [];
        
        foreach($this->actions as $actionName => $actionParams) {
            
            if ($actionParams instanceof ActionInterface) {
                $actions[] = $actionParams;
                continue;
            }
            
            if (!is_string($actionName)) {
                continue;
            }
            
            if (!is_array($actionParams)) {
                $actionParams = [];
            }
            
            try {
                $action = $this->actionFactory->createAction($actionName, $actionParams);
                $actions[] = $action;
            } catch (ActionCreateException $e) {
                // ignore exception but we log:
                $this->getLogger()->notice(
                    sprintf('Unable to create action %s', $actionName),
                    ['exception' => $e]
                );
            }
        }
        
        return $this->createdActions = $actions;
    }
    
    /**
     * Verify the mime type.
     *
     * @param ResourceInterface $resource
     * @return string The verified mime type.
     * @throws UnsupportedImageException
     */
    protected function verifyMimeType(ResourceInterface $resource): string
    {
        $detector = new FinfoMimeTypeDetector();
        
        switch ($resource) {
            case $resource instanceof Stream:
                $mimeType = $detector->detectMimeTypeFromBuffer((string)$resource->stream());
                break;
            case $resource instanceof File:
                $mimeType = $detector->detectMimeTypeFromFile($resource->file()->getFile());
                break;
            default:
                throw new UnsupportedImageException($resource);
        }
        
        if (!$this->isSupportedMimeType($mimeType)) {
            throw new UnsupportedImageException($resource);
        }
        
        return (string)$mimeType;
    }
    
    /**
     * Determines if the the mime type is supported.
     *
     * @param mixed $mimeType
     * @return bool True if supported, otherwise false.
     */
    protected function isSupportedMimeType(mixed $mimeType): bool
    {
        if (!is_string($mimeType)) {
            return false;
        }
        
        $formats = new ImageFormats();
        $format = $formats->getFormat($mimeType);
        
        if (
            is_null($format)
            || !in_array($mimeType, $this->supportedMimeTypes)
        ) {
            return false;
        }
        
        return true;
    }
}