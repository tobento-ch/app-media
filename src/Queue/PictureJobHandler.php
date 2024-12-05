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
 
namespace Tobento\App\Media\Queue;

use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\Service\Queue\JobHandlerInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\JobException;

/**
 * PictureJobHandler
 */
class PictureJobHandler implements JobHandlerInterface
{
    /**
     * Create a new PictureJobHandler instance.
     *
     * @param PictureGeneratorInterface $pictureGenerator
     */
    public function __construct(
        private PictureGeneratorInterface $pictureGenerator,
    ) {}

    /**
     * Handles the specified job.
     *
     * @param JobInterface $job
     * @return void
     * @throws JobException
     */
    public function handleJob(JobInterface $job): void
    {
        if ($job->getPayload()['regenerate'] === true) {
            $this->pictureGenerator->regenerate(
                path: $job->getPayload()['path'],
                resource: unserialize($job->getPayload()['resource']),
                definition: unserialize($job->getPayload()['definition']),
                queue: false,
            );
            
            return;
        }
        
        $this->pictureGenerator->generate(
            path: $job->getPayload()['path'],
            resource: unserialize($job->getPayload()['resource']),
            definition: unserialize($job->getPayload()['definition']),
            queue: false,
        );
    }
}