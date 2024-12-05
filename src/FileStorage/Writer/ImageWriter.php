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
 
namespace Tobento\App\Media\FileStorage\Writer;

use Psr\Http\Message\StreamInterface;
use Tobento\App\Media\Exception\ImageProcessException;
use Tobento\App\Media\Exception\UnsupportedImageException;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\WriteResponse;
use Tobento\App\Media\FileStorage\WriteResponseInterface;
use Tobento\App\Media\Image\ImageProcessorInterface;
use Tobento\Service\Imager\Message\MessagesFactoryInterface;
use Tobento\Service\Imager\Message\MessagesFactory as ImagerMessagesFactory;

/**
 * ImageWriter
 */
class ImageWriter implements WriterInterface
{
    /**
     * Create a new ImageWriter instance.
     *
     * @param ImageProcessorInterface $imageProcessor
     * @param null|MessagesFactoryInterface $messagesFactory
     */
    public function __construct(
        protected ImageProcessorInterface $imageProcessor,
        protected null|MessagesFactoryInterface $messagesFactory = null,
    ) {}
    
    /**
     * Write.
     *
     * @param string $path
     * @param StreamInterface $stream
     * @param string $originalFilename
     * @return null|WriteResponseInterface Null if not supports writing for the given path and stream.
     * @throws WriteException
     */
    public function write(
        string $path,
        StreamInterface $stream,
        string $originalFilename,
    ): null|WriteResponseInterface {
        try {
            $encoded = $this->imageProcessor->processFromStream($stream);
        } catch (UnsupportedImageException $e) {
            return null;
        } catch (ImageProcessException $e) {
            throw new WriteException(
                message: 'Image processing failed for the file :path.',
                parameters: [':path' => $path],
                code: (int)$e->getCode(),
                previous: $e,
            );
        }
        
        $messages = $this->messagesFactory()->createMessagesFromActions(
            actions: $encoded->actions()->withoutProcessedBy()
        );

        // rebuild path as mime type might have changed:
        $dirname = str_replace(['\\'], '/', pathinfo($path, PATHINFO_DIRNAME));
        $filename = pathinfo($path, PATHINFO_FILENAME);

        if ($dirname === '.') {
            $path = sprintf('%s.%s', $filename, $encoded->extension());
        } else {
            $path = sprintf('%s/%s.%s', $dirname, $filename, $encoded->extension());
        }

        return new WriteResponse(
            path: $path,
            content: $encoded,
            originalFilename: $originalFilename,
            messages: $messages
        );
    }
    
    /**
     * Returns the messages factory.
     *
     * @return MessagesFactoryInterface
     */
    protected function messagesFactory(): MessagesFactoryInterface
    {
        if (!is_null($this->messagesFactory)) {
            return $this->messagesFactory;
        }
        
        return $this->messagesFactory = new ImagerMessagesFactory();
    }
}