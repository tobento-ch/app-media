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
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\FileWriteException;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\Picture\CreatedPictureInterface;
use Tobento\Service\Picture\ImgInterface;
use Tobento\Service\Picture\PictureInterface;
use Tobento\Service\Picture\Sources;
use Tobento\Service\Picture\SourcesInterface;
use Tobento\Service\Picture\SrcInterface;
use Tobento\Service\Picture\Srcset;

/**
 * SavedPictureFactory
 */
class SavedPictureFactory
{
    /**
     * Create a new SavedPictureFactory instance.
     *
     * @param StorageInterface $storage
     */
    public function __construct(
        protected StorageInterface $storage,
    ) {}
    
    /**
     * Create a new picture from a create picture.
     *
     * @param string $path
     * @param CreatedPictureInterface $picture
     * @return PictureInterface
     * @throws PictureException
     */
    public function createFromCreated(string $path, CreatedPictureInterface $picture): PictureInterface
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $path = sprintf('%s/%s', $dirname, $filename);
        
        return new SavedPicture(
            img: $this->createImg($path, $picture),
            sources: $this->createSources($path, $picture),
            attributes: $picture->attributes(),
            options: $picture->options(),
        );
    }
    
    /**
     * Create img.
     *
     * @param string $path
     * @param CreatedPictureInterface $picture
     * @return ImgInterface
     */
    protected function createImg(
        string $path,
        CreatedPictureInterface $picture,
    ): ImgInterface {
        // handle img src:
        $imgSrc = $this->storeEncodedSrc($path, $picture->img()->src());
        $img = $picture->img()->withSrc($imgSrc);
        
        // handle img srcset:
        if ($picture->img()->srcset()) {
            $srces = [];
            
            foreach($picture->img()->srcset() as $src) {
                $srces[] = $this->storeEncodedSrc($path, $src);
            }
            
            $img = $img->withSrcset(new Srcset(...$srces));
        }
        
        return $img;
    }
    
    /**
     * Create sources.
     *
     * @param string $path
     * @param PictureInterface $picture
     * @return SourcesInterface
     */
    protected function createSources(
        string $path,
        PictureInterface $picture,
    ): SourcesInterface {
        $sources = [];
        
        foreach($picture->sources() as $source) {
            $srces = [];
                        
            foreach($source->srcset() as $src) {
                $srces[] = $this->storeEncodedSrc($path, $src);
            }
            
            $sources[] = $source->withSrcset(new Srcset(...$srces));
        }
        
        return new Sources(...$sources);
    }

    /**
     * Store encoded src to file storage.
     *
     * @param string $path
     * @param SrcInterface $src
     * @return SrcInterface
     */
    protected function storeEncodedSrc(string $path, SrcInterface $src): SrcInterface
    {
        if (is_null($src->encoded())) {
            throw new PictureException(
                message: sprintf('Encoded src is null for the path %s', $path)
            );
        }
        
        $encoded = $src->encoded();
        $path = $this->generateUniquePath($path, $src);
        $options = $src->options();
        $options['storage'] = $this->storage->name();
        
        try {
            $this->storage->write(
                path: $path,
                content: (string)$src->encoded(),
            );
            
            $file = $this->storage->with('url')->file($path);
            
            return $src
                ->withPath($path)
                ->withOptions($options)
                ->withWidth($encoded->width())
                ->withHeight($encoded->height())
                ->withMimeType($encoded->mimeType())
                ->withUrl($file->url())
                ->withEncoded(null);
        } catch (FileWriteException|FileNotFoundException $e) {
            throw new PictureException(
                message: sprintf('Writing src to storage failed for path %s: %s', $path, $e->getMessage()),
                code: (int)$e->getCode(),
                previous: $e,
            );
        }
    }
    
    /**
     * Returns a generated unique path.
     *
     * @param string $path
     * @param SrcInterface $src
     * @return string
     */
    protected function generateUniquePath(string $path, SrcInterface $src): string
    {
        $originalPath = $path;
        $path = $this->buildPath($path, $src);
        $i = 1;

        while ($this->storage->exists($path)) {
            $path = $originalPath.'-'.$i++;
            $path = $this->buildPath($path, $src);
        }

        return $path;
    }
    
    /**
     * Returns the build path.
     *
     * @param string $path
     * @param SrcInterface $src
     * @return string
     */
    protected function buildPath(string $path, SrcInterface $src): string
    {
        $encoded = $src->encoded();
        
        $path = sprintf('%s-%dx%d', $path, $encoded->width(), $encoded->height());
        
        if ($src->descriptor()) {
            $descriptor = preg_replace('/[^A-Za-z0-9_\-]/', '-', trim($src->descriptor()));
            $path = sprintf('%s-%s', $path, $descriptor);
        }
        
        // for browser cache busting if cropped e.g.
        $path = sprintf('%s-%d', $path, time());
        
        return sprintf('%s.%s', $path, $encoded->extension());
    }
}