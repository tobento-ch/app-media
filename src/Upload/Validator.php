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

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\UploadedFileErrorException;
use Tobento\App\Media\Exception\UploadedFileException;

/**
 * Validator
 */
class Validator implements ValidatorInterface
{
    /**
     * Create a new Validator instance.
     *
     * @param array<array-key, string> $allowedExtensions
     * @param bool $strictFilenameCharacters
     * @param int $maxFilenameLength
     * @param null|int $maxFileSizeInKb Null unlimited
     */
    public function __construct(
        protected array $allowedExtensions = ['jpg', 'png', 'gif', 'webp'],
        protected bool $strictFilenameCharacters = true,
        protected int $maxFilenameLength = 255,
        protected null|int $maxFileSizeInKb = null,
    ) {}
    
    /**
     * Validates the uploaded file.
     *
     * @param UploadedFileInterface $file
     * @return void
     * @throws UploadedFileException
     */
    public function validateUploadedFile(UploadedFileInterface $file): void
    {
        $this->validateFileError($file);
        
        if ($this->strictFilenameCharacters) {
            $this->validateFilenameCharacters($file);
        }
                
        $this->validateFilenameLength($file, $this->maxFilenameLength);
        
        $this->validateMimeTypeAndExtension($file, $this->allowedExtensions);
        
        $this->validateFileSize($file, $this->maxFileSizeInKb);
    }
    
    /**
     * Validates the file error.
     *
     * @param UploadedFileInterface $file
     * @return void
     * @throws UploadedFileException
     */
    protected function validateFileError(UploadedFileInterface $file): void
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new UploadedFileErrorException($file);
        }
    }
    
    /**
     * Validates the filename characters.
     *
     * @param UploadedFileInterface $file
     * @return void
     * @throws UploadedFileException
     */
    protected function validateFilenameCharacters(UploadedFileInterface $file): void
    {
        if (empty($file->getClientFilename())) {
            return;
        }
        
        if ((bool)preg_match('/^[a-zA-Z0-9_\-\. ]+$/u', (string)$file->getClientFilename()) === false) {
            throw new UploadedFileException(
                uploadedFile: $file,
                message: 'The filename :name has invalid characters. Only alphanumeric characters, hyphen, spaces, and periods are allowed.',
                parameters: [':name' => (string)$file->getClientFilename()],
            );
        }
    }
    
    /**
     * Validates the filename length.
     *
     * @param UploadedFileInterface $file
     * @param int $maxFilenameLength
     * @return void
     * @throws UploadedFileException
     */
    protected function validateFilenameLength(UploadedFileInterface $file, int $maxFilenameLength): void
    {
        if (mb_strlen((string)$file->getClientFilename()) > $maxFilenameLength) {
            throw new UploadedFileException(
                uploadedFile: $file,
                message: 'The filename :name must have at most :num characters.',
                parameters: [
                    ':name' => (string)$file->getClientFilename(),
                    ':num' => $maxFilenameLength,
                ],
            );
        }
    }
    
    /**
     * Validates that the file mime type and extension.
     *
     * @param UploadedFileInterface $file
     * @param array<array-key, string> $allowedExtensions
     * @return void
     * @throws UploadedFileException
     */
    protected function validateMimeTypeAndExtension(
        UploadedFileInterface $file,
        array $allowedExtensions
    ): void {
        $filename = (string)$file->getClientFilename();
        $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check that the extension is allowed:
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new UploadedFileException(
                uploadedFile: $file,
                message: 'The extension :extension of the file :name is disallowed. Allowed extensions are :extensions.',
                parameters: [
                    ':extension' => $fileExtension,
                    ':name' => (string)$file->getClientFilename(),
                    ':extensions' => implode(',', $allowedExtensions),
                ],
            );
        }
        
        // Check that the extension of the file is consistent with its content
        // and the mime type is valid:
        $fileMimeType = $this->detectMimeType($file);
        $allowedMimeTypes = $this->lookupMimeTypes($fileExtension, $file);
        
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            throw new UploadedFileException(
                uploadedFile: $file,
                message: 'The mime type :type of the file :name is invalid. Allowed mime types are :types.',
                parameters: [
                    ':type' => $fileMimeType,
                    ':name' => (string)$file->getClientFilename(),
                    ':types' => implode(',', $allowedMimeTypes),
                ],
            );
        }
        
        // Check that the client media type is consistent with its content:
        if (is_string($file->getClientMediaType())) {
            if ($fileMimeType !== $file->getClientMediaType()) {
                throw new UploadedFileException(
                    uploadedFile: $file,
                    message: 'The mime type :type of the file :name is invalid. Allowed mime types are :types.',
                    parameters: [
                        ':type' => $file->getClientMediaType(),
                        ':name' => (string)$file->getClientFilename(),
                        ':types' => implode(',', $allowedMimeTypes),
                    ],
                );
            }
        }
    }
    
    /**
     * Validates the file size.
     *
     * @param UploadedFileInterface $file
     * @param null|int $maxFileSizeInKb Null unlimited
     * @return void
     * @throws UploadedFileException
     */
    protected function validateFileSize(UploadedFileInterface $file, null|int $maxFileSizeInKb): void
    {
        if (is_null($maxFileSizeInKb) || is_null($file->getSize())) {
            return;
        }
        
        $fileSizeInKb = $file->getSize() * 1024;
        
        if ($fileSizeInKb > $this->maxFileSizeInKb) {
            throw new UploadedFileException(
                uploadedFile: $file,
                message: 'The file :name exceeded the max upload size of :num KB.',
                parameters: [':name' => (string)$file->getClientFilename(), ':num' => $maxFileSizeInKb],
            );
        }
    }
    
    /**
     * Returns the detected mime type for the given file.
     *
     * @param UploadedFileInterface $file
     * @return string
     * @throws UploadedFileException
     */
    protected function detectMimeType(UploadedFileInterface $file): string
    {
        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeTypeFromBuffer((string)$file->getStream());

        if (is_null($mimeType)) {
            throw new UploadedFileException(
                uploadedFile: $file,
                message: 'Unable to detect the mime type for the file :name.',
                parameters: [':name' => (string)$file->getClientFilename()],
            );
        }
        
        return $mimeType;
    }
    
    /**
     * Returns the mime types for the given extension.
     *
     * @param string $extension
     * @param UploadedFileInterface $file
     * @return array<array-key, string>
     * @throws UploadedFileException
     */
    protected function lookupMimeTypes(string $extension, UploadedFileInterface $file): array
    {
        $map = new GeneratedExtensionToMimeTypeMap();
        $mimeType = $map->lookupMimeType($extension);
        
        if (is_null($mimeType)) {
            throw new UploadedFileException(
                uploadedFile: $file,
                message: 'Unable to determine the mime types for the extension :extension.',
                parameters: [':extension' => $extension],
            );
        }
        
        return [$mimeType];
    }
}