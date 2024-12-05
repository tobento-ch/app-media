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

namespace Tobento\App\Media\Test\FileStorage\Writer;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\Writer\SvgSanitizerWriter;
use Tobento\App\Media\FileStorage\Writer\WriterInterface;
use Tobento\App\Media\FileStorage\WriteResponseInterface;
use Tobento\App\Media\Test\Factory;
use Tobento\Service\Message\MessagesInterface;

class SvgSanitizerWriterTest extends TestCase
{
    public function testImplementsWriterInterface()
    {
        $this->assertInstanceof(WriterInterface::class, new SvgSanitizerWriter());
    }
    
    public function testWriteMethodReturnsNullIfNotSvgFile()
    {
        $writer = new SvgSanitizerWriter();
        
        $writeResponse = $writer->write(
            path: 'file.txt',
            stream: Factory::createStreamFactory()->createStream(''),
            originalFilename: 'orgfilename.txt',
        );
        
        $this->assertNull($writeResponse);
    }
    
    public function testWriteMethodCleansSvg()
    {
        $writer = new SvgSanitizerWriter();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 100 100" onload="alert(1)"><path d="M0,100H100V90H0ZM100,50H66.67V0H33.33V50H0L50,83.33Z"/></svg>';
        
        $writeResponse = $writer->write(
            path: 'file.svg',
            stream: Factory::createStreamFactory()->createStream($svg),
            originalFilename: 'orgfilename.svg',
        );
        
        $this->assertFalse(str_contains($writeResponse->content(), 'alert(1)'));
        $this->assertSame('file.svg', $writeResponse->path());
        $this->assertSame('orgfilename.svg', $writeResponse->originalFilename());
        $this->assertFalse($writeResponse->messages()->has());
    }
    
    public function testWriteMethodThrowsWriteExecptionIfSanitizingFails()
    {
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('SVG sanitizing failed for the file :path.');
        
        $writer = new SvgSanitizerWriter();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg">';
        
        $writeResponse = $writer->write(
            path: 'file.svg',
            stream: Factory::createStreamFactory()->createStream($svg),
            originalFilename: 'orgfilename.svg',
        );
    }
    
    public function testWriteMethodLogsIssues()
    {
        $logger = new Logger('name');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);
        
        $writer = new SvgSanitizerWriter();
        $writer->setLogger($logger);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 100 100" onload="alert(1)"><path d="M0,100H100V90H0ZM100,50H66.67V0H33.33V50H0L50,83.33Z"/></svg>';
        
        $writeResponse = $writer->write(
            path: 'file.svg',
            stream: Factory::createStreamFactory()->createStream($svg),
            originalFilename: 'orgfilename.svg',
        );
        
        $this->assertTrue($testHandler->hasRecord('SVG has sanitizing issues for the file :path.', 'info'));
    }    
}