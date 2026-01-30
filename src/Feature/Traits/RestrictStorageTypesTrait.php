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
 
namespace Tobento\App\Media\Feature\Traits;

use Closure;
use Tobento\Service\FileStorage\StorageInterface;

trait RestrictStorageTypesTrait
{
    /**
     * @param StorageInterface $storage
     * @param array $allowedStorageTypes e.g. ['public'], ['private'], or ['public', 'private']
     * @param class-string<\Throwable>|Closure $exception Either an exception class-string
     *    or a Closure(string $message, StorageInterface $storage): \Throwable
     */
    protected function restrictStorageTypes(
        StorageInterface $storage,
        array $allowedStorageTypes,
        string|Closure $exception
    ): void {
        // Helper to throw the exception
        $throw = function (string $message) use ($exception, $storage) {
            if ($exception instanceof Closure) {
                throw $exception($message, $storage);
            }
            throw new $exception(message: $message);
        };

        $type = $storage->type();

        if (!in_array($type, $allowedStorageTypes, true)) {
            $throw(sprintf(
                'Feature allows only [%s] storage types, but the storage "%s" is of type "%s".',
                implode(', ', $allowedStorageTypes),
                $storage->name(),
                $type,
            ));
        }
    }
}