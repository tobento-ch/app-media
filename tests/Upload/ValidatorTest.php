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

namespace Tobento\App\Media\Test\Upload;

use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Exception\UploadedFileException;
use Tobento\App\Media\Upload\Validator;
use Tobento\App\Media\Upload\ValidatorInterface;
use Tobento\App\Testing\Http\FileFactory;

class ValidatorTest extends TestCase
{
    public function testThatImplementsInterface()
    {
        $this->assertInstanceof(ValidatorInterface::class, new Validator());
    }

    public function testFailsIfUploadErr()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('No file was uploaded.');
        
        (new Validator(
            allowedExtensions: ['jpg']
        ))->validateUploadedFile(
            file: (new FileFactory())->createImage(filename: 'profile.jpg')->setError(UPLOAD_ERR_NO_FILE)
        );
    }
    
    public function testPassesIfAllowedExtension()
    {
        (new Validator(
            allowedExtensions: ['jpg']
        ))->validateUploadedFile(
            file: (new FileFactory())->createImage(filename: 'profile.jpg')
        );
        
        $this->assertTrue(true);
    }

    public function testFailsIfNotAllowedExtension()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('The extension :extension of the file :name is disallowed. Allowed extensions are :extensions.');
        
        (new Validator(
            allowedExtensions: ['gif']
        ))->validateUploadedFile(
            file: (new FileFactory())->createImage(filename: 'profile.jpg')
        );
    }

    public function testPassesIfClientMimeTypeIsNull()
    {
        (new Validator(
            allowedExtensions: ['txt']
        ))->validateUploadedFile(
            file: (new FileFactory())->createFileWithContent(
                filename: 'file.txt',
                content: 'Lorem',
                mimeType: null,
            )
        );
        
        $this->assertTrue(true);
    }
    
    public function testFailsIfClientMimeTypeIsNotConsistentWithItsContent()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('The mime type :type of the file :name is invalid. Allowed mime types are :types.');
        
        (new Validator(
            allowedExtensions: ['txt']
        ))->validateUploadedFile(
            file: (new FileFactory())->createFileWithContent(
                filename: 'file.txt',
                content: 'Lorem',
                mimeType: 'image/jpeg'
            )
        );
    }
    
    public function testFailsIfFileExtensionIsNotConsistentWithItsContent()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('The mime type :type of the file :name is invalid. Allowed mime types are :types.');
        
        (new Validator(
            allowedExtensions: ['jpg', 'gif']
        ))->validateUploadedFile(
            file: (new FileFactory())->createFileWithContent(
                filename: 'image.jpg',
                content: 'GIF87a',
                mimeType: 'image/jpeg'
            )
        );
    }
    
    public function testFailsIfMimeTypesCannotBeDeterminedByExtension()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('Unable to determine the mime types for the extension :extension.');
        
        (new Validator(
            allowedExtensions: ['unsupported']
        ))->validateUploadedFile(
            file: (new FileFactory())->createFileWithContent(
                filename: 'file.unsupported',
                content: 'Lorem',
            )
        );
    }
    
    public function testPassesIfFileExtensionIsUppercase()
    {
        (new Validator(
            allowedExtensions: ['txt']
        ))->validateUploadedFile(
            file: (new FileFactory())->createFileWithContent(
                filename: 'file.TXT',
                content: 'Lorem',
            )
        );
        
        $this->assertTrue(true);
    }

    public function testFailsIfFilenameMissing()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('The extension :extension of the file :name is disallowed. Allowed extensions are :extensions.');
        
        (new Validator(
            allowedExtensions: ['jpg'],
        ))->validateUploadedFile(
            file: (new FileFactory())->createImage(filename: 'file.')
        );
    }
    
    public function testFailsIfFilenameExtensionMissing()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('The extension :extension of the file :name is disallowed. Allowed extensions are :extensions.');
        
        (new Validator(
            allowedExtensions: ['jpg'],
        ))->validateUploadedFile(
            file: (new FileFactory())->createImage(filename: 'file')
        );
    }
    
    public function testFailsIfInvalidFilenameCharactersWhenStrict()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('The filename :name has invalid characters. Only alphanumeric characters, hyphen, spaces, and periods are allowed.');
        
        (new Validator(
            allowedExtensions: ['jpg'],
            strictFilenameCharacters: true,
        ))->validateUploadedFile(
            file: (new FileFactory())->createImage(filename: 'a%bc.jpg')
        );
    }
    
    public function testPassesIfAnyFilenameCharactersWhenNotStrict()
    {
        (new Validator(
            allowedExtensions: ['jpg'],
            strictFilenameCharacters: false,
        ))->validateUploadedFile(
            file: (new FileFactory())->createImage(filename: 'Foo /a%bc.jpg')
        );
        
        $this->assertTrue(true);
    }
    
    public function testFailsIfMaxFilenameLengthExceeded()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('The filename :name must have at most :num characters.');
        
        (new Validator(
            allowedExtensions: ['jpg'],
            maxFilenameLength: 7,
        ))->validateUploadedFile(
            file: (new FileFactory())->createImage(filename: 'file.jpg')
        );
    }
    
    public function testFailsIfMaxFileSizeExceeded()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('The file :name exceeded the max upload size of :num KB.');
        
        (new Validator(
            allowedExtensions: ['txt'],
            maxFileSizeInKb: 6,
        ))->validateUploadedFile(
            file: (new FileFactory())->createFileWithContent(
                filename: 'file.txt',
                content: 'Lorem',
            )->setSize(5)
        );
    }
}