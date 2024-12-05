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
 
namespace Tobento\App\Media\Exception;

use Exception;
use Throwable;

/**
 * WriteException
 */
class WriteException extends Exception
{
    /**
     * Create a new UploadedFileException.
     *
     * @param string $message The message
     * @param array $parameters Any message parameters
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        string $message = '',
        protected array $parameters = [],
        int $code = 0,
        null|Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the message parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        return $this->parameters;
    }
}