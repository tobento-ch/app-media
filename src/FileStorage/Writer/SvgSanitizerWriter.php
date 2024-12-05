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

use enshrined\svgSanitize\Sanitizer;
use Psr\Http\Message\StreamInterface;
use Tobento\App\Logging\LoggerTrait;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\WriteResponse;
use Tobento\App\Media\FileStorage\WriteResponseInterface;

/**
 * SvgSanitizerWriter
 */
class SvgSanitizerWriter implements WriterInterface
{
    use LoggerTrait;
    
    /**
     * @var Sanitizer
     */
    protected Sanitizer $sanitizer;
    
    /**
     * Create a new SvgSanitizerWriter instance.
     *
     * @param null|Sanitizer $sanitizer
     * @param bool $logIssues
     */
    public function __construct(
        null|Sanitizer $sanitizer = null,
        protected bool $logIssues = true,
    ) {
        if (is_null($sanitizer)) {
            $sanitizer = new Sanitizer();
            $sanitizer->removeXMLTag(true);
            $sanitizer->removeRemoteReferences(true);
            //$sanitizer->minify(true);
        }
        
        $this->sanitizer = $sanitizer;
    }
    
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
        if (!str_ends_with($path, '.svg')) {
            return null;
        }
        
        $cleanSVG = $this->sanitizer->sanitize((string)$stream);
        $issues = $this->sanitizer->getXmlIssues();
        
        if ($this->logIssues && !empty($issues)) {
            $this->getLogger()->info(
                message: sprintf('SVG has sanitizing issues for the file :path.', $path),
                context: ['issues' => $issues],
            );
        }
        
        if (!is_string($cleanSVG)) {
            throw new WriteException(
                message: 'SVG sanitizing failed for the file :path.',
                parameters: [':path' => $path],
            );
        }
        
        return new WriteResponse(path: $path, content: $cleanSVG, originalFilename: $originalFilename);
    }
}