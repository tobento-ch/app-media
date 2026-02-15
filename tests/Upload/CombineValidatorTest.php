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
use Tobento\App\Media\Upload\CombineValidator;
use Tobento\App\Media\Upload\ValidatorInterface;
use Tobento\App\Testing\Http\FileFactory;

class CombineValidatorTest extends TestCase
{
    public function testImplementsInterface()
    {
        $this->assertInstanceof(ValidatorInterface::class, new CombineValidator());
    }
    
    public function testSupportsExtensionsMethod()
    {
        $this->assertSame([], new CombineValidator()->supportsExtensions());
    }
    
    public function testDispatchesToMatchingValidator()
    {
        $csvValidator = new class implements ValidatorInterface {
            public bool $called = false;

            public function supportsExtensions(): array
            {
                return ['csv'];
            }

            public function validateUploadedFile($file): void
            {
                $this->called = true;
            }
        };

        $fallbackValidator = new class implements ValidatorInterface {
            public bool $called = false;

            public function supportsExtensions(): array
            {
                return [];
            }

            public function validateUploadedFile($file): void
            {
                $this->called = true;
            }
        };

        $validator = new CombineValidator($csvValidator, $fallbackValidator);

        $validator->validateUploadedFile(
            new FileFactory()->createFileWithContent(
                filename: 'file.csv',
                content: "a,b,c\n1,2,3",
                mimeType: 'text/csv'
            )
        );

        $this->assertTrue($csvValidator->called);
        $this->assertFalse($fallbackValidator->called);
    }

    public function testFallsBackWhenNoExtensionMatches()
    {
        $csvValidator = new class implements ValidatorInterface {
            public bool $called = false;

            public function supportsExtensions(): array
            {
                return ['csv'];
            }

            public function validateUploadedFile($file): void
            {
                $this->called = true;
            }
        };

        $fallbackValidator = new class implements ValidatorInterface {
            public bool $called = false;

            public function supportsExtensions(): array
            {
                return []; // supports all
            }

            public function validateUploadedFile($file): void
            {
                $this->called = true;
            }
        };

        $validator = new CombineValidator($csvValidator, $fallbackValidator);

        $validator->validateUploadedFile(
            new FileFactory()->createFileWithContent(
                filename: 'file.txt',
                content: "hello",
                mimeType: 'text/plain'
            )
        );

        $this->assertFalse($csvValidator->called);
        $this->assertTrue($fallbackValidator->called);
    }

    public function testThrowsIfNoValidatorSupportsExtension()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('No validator available for file :name.');

        $validator = new CombineValidator(
            new class implements ValidatorInterface {
                public function supportsExtensions(): array
                {
                    return ['csv'];
                }
                public function validateUploadedFile($file): void {}
            }
        );

        $validator->validateUploadedFile(
            new FileFactory()->createFileWithContent(
                filename: 'file.jpg',
                content: "binary",
                mimeType: 'image/jpeg'
            )
        );
    }

    public function testValidatorsAreCheckedInOrder()
    {
        $first = new class implements ValidatorInterface {
            public bool $called = false;
            public function supportsExtensions(): array { return []; }
            public function validateUploadedFile($file): void { $this->called = true; }
        };

        $second = new class implements ValidatorInterface {
            public bool $called = false;
            public function supportsExtensions(): array { return []; }
            public function validateUploadedFile($file): void { $this->called = true; }
        };

        $validator = new CombineValidator($first, $second);

        $validator->validateUploadedFile(
            new FileFactory()->createFileWithContent(
                filename: 'file.any',
                content: "x",
                mimeType: 'text/plain'
            )
        );

        $this->assertTrue($first->called);
        $this->assertFalse($second->called);
    }
}