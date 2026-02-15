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

use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\UploadedFileException;

/**
 * Validates uploaded PDF files by extending the base upload validator
 * with PDF‑specific security checks such as header validation and
 * detection of unsafe PDF features (JavaScript, encryption, embedded files).
 */
class PdfValidator extends Validator
{
    /**
     * Returns the file extensions handled by this specialized PDF validator (lowercase, without dot).
     *
     * @return array<int, string>
     */
    public function supportsExtensions(): array
    {
        return ['pdf'];
    }
    
    /**
     * Validate the uploaded PDF file.
     *
     * @param UploadedFileInterface $file
     * @return void
     *
     * @throws UploadedFileException If the PDF is invalid or unsafe.
     */
    public function validateUploadedFile(UploadedFileInterface $file): void
    {
        // Run base validation first (extension, mime, size, etc.)
        parent::validateUploadedFile($file);
        
        // Run PDF-specific validation
        $this->validatePdfStructure($file);
    }

    /**
     * Perform lightweight structural and security checks on the PDF content.
     *
     * @param UploadedFileInterface $file
     * @return void
     * @throws UploadedFileException
     */
    protected function validatePdfStructure(UploadedFileInterface $file): void
    {
        $content = strtolower((string)$file->getStream());

        $checks = [
            '/encrypt' => 'encryption',
            '/js' => 'JavaScript',
            '/javascript' => 'JavaScript',
            '/embeddedfile' => 'embedded files',
            '/launch' => 'launch actions',
            '/openaction' => 'OpenAction',
            '/aa' => 'additional actions (AA)',
        ];

        foreach ($checks as $needle => $feature) {
            if (str_contains($content, $needle)) {
                throw new UploadedFileException(
                    uploadedFile: $file,
                    message: 'PDF contains :feature, which is not allowed.',
                    parameters: [
                        ':feature' => $feature,
                    ],
                );
            }
        }
    }
}