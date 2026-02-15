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
use Tobento\App\Media\Upload\PdfValidator;
use Tobento\App\Media\Upload\ValidatorInterface;
use Tobento\App\Testing\Http\FileFactory;

class PdfValidatorTest extends TestCase
{
    public function testImplementsInterface()
    {
        $this->assertInstanceOf(ValidatorInterface::class, new PdfValidator());
    }

    public function testSupportsExtensionsMethod()
    {
        $this->assertSame(['pdf'], new PdfValidator()->supportsExtensions());
    }

    public function testValidPdfPasses()
    {
        $validator = new PdfValidator(allowedExtensions: ['pdf']);

        $validator->validateUploadedFile(
            file: new FileFactory()->createFileWithContent(
                filename: 'file.pdf',
                content: "%PDF-1.7\nSome content here",
                mimeType: 'application/pdf'
            )
        );

        $this->assertTrue(true);
    }

    public function testFailsIfEncryptedMixedCase()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('PDF contains :feature, which is not allowed.');

        new PdfValidator(allowedExtensions: ['pdf'])
            ->validateUploadedFile(
                file: new FileFactory()->createFileWithContent(
                    filename: 'file.pdf',
                    content: "%PDF-1.7\n/EnCrYpT 123",
                    mimeType: 'application/pdf'
                )
            );
    }

    public function testFailsIfContainsJavaScriptMixedCase()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('PDF contains :feature, which is not allowed.');

        new PdfValidator(allowedExtensions: ['pdf'])
            ->validateUploadedFile(
                file: new FileFactory()->createFileWithContent(
                    filename: 'file.pdf',
                    content: "%PDF-1.7\n/JaVaScRiPt (alert('x'))",
                    mimeType: 'application/pdf'
                )
            );
    }

    public function testFailsIfContainsEmbeddedFileMixedCase()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('PDF contains :feature, which is not allowed.');

        new PdfValidator(allowedExtensions: ['pdf'])
            ->validateUploadedFile(
                file: new FileFactory()->createFileWithContent(
                    filename: 'file.pdf',
                    content: "%PDF-1.7\n/EmBeDdEdFiLe",
                    mimeType: 'application/pdf'
                )
            );
    }

    public function testFailsIfContainsLaunchActionMixedCase()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('PDF contains :feature, which is not allowed.');

        new PdfValidator(allowedExtensions: ['pdf'])
            ->validateUploadedFile(
                file: new FileFactory()->createFileWithContent(
                    filename: 'file.pdf',
                    content: "%PDF-1.7\n/LaUnCh (cmd.exe)",
                    mimeType: 'application/pdf'
                )
            );
    }

    public function testFailsIfContainsOpenAction()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('PDF contains :feature, which is not allowed.');

        new PdfValidator(allowedExtensions: ['pdf'])
            ->validateUploadedFile(
                file: new FileFactory()->createFileWithContent(
                    filename: 'file.pdf',
                    content: "%PDF-1.7\n/OpenAction << /JS (alert('x')) >>",
                    mimeType: 'application/pdf'
                )
            );
    }

    public function testFailsIfContainsAdditionalActionsAA()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('PDF contains :feature, which is not allowed.');

        new PdfValidator(allowedExtensions: ['pdf'])
            ->validateUploadedFile(
                file: new FileFactory()->createFileWithContent(
                    filename: 'file.pdf',
                    content: "%PDF-1.7\n/AA << /O (alert('x')) >>",
                    mimeType: 'application/pdf'
                )
            );
    }

    public function testFailsIfExtensionNotPdf()
    {
        $this->expectException(UploadedFileException::class);

        new PdfValidator(allowedExtensions: ['pdf'])
            ->validateUploadedFile(
                file: new FileFactory()->createFileWithContent(
                    filename: 'file.txt',
                    content: "%PDF-1.7\nSome content",
                    mimeType: 'text/plain'
                )
            );
    }
}