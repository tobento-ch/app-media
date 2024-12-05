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
 
namespace Tobento\App\Media\Upload;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface as Psr17UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\CreateUploadedFileException;
use Tobento\Service\FileStorage\FileInterface;

/**
 * UploadedFileFactory
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * Create a new UploadedFileFactory instance.
     *
     * @param Psr17UploadedFileFactoryInterface $uploadedFileFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        protected Psr17UploadedFileFactoryInterface $uploadedFileFactory,
        protected StreamFactoryInterface $streamFactory,
    ) {}
    
    /**
     * Create uploaded file from the given remote url.
     *
     * @param string $url
     * @return UploadedFileInterface
     * @throws CreateUploadedFileException
     */
    public function createFromRemoteUrl(string $url): UploadedFileInterface
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);

        if (empty($ret)) {
            $error = curl_error($ch);
            curl_close($ch);
            
            throw new CreateUploadedFileException(
                message: 'Creating uploaded file from remote file :url failed: :error',
                parameters: [':url' => $url, ':error' => $error],
            );
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new CreateUploadedFileException(
                message: 'Creating uploaded file from remote file :url failed as not found.',
                parameters: [':url' => $url],
            );
        }
        
        $stream = $this->streamFactory->createStream((string)$ret);
        
        return $this->uploadedFileFactory->createUploadedFile(
            stream: $stream,
            size: (int) $stream->getSize(),
            clientFilename: pathinfo($url, PATHINFO_BASENAME),
        );
    }
    
    /**
     * Create uploaded file from the given storage file.
     *
     * @param FileInterface $file
     * @return UploadedFileInterface
     * @throws CreateUploadedFileException
     */
    public function createFromStorageFile(FileInterface $file): UploadedFileInterface
    {
        if (is_null($file->stream())) {
            throw new CreateUploadedFileException(
                message: 'Writing storage file :file failed as no stream available.',
                parameters: [':file' => $file->path()],
            );
        }
        
        return $this->uploadedFileFactory->createUploadedFile(
            stream: $file->stream(),
            size: (int) $file->stream()->getSize(),
            clientFilename: $file->name(),
        );
    }
}