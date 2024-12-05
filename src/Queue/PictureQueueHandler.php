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

use Tobento\App\Media\Picture\QueueHandlerInterface;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\Job;

/**
 * PictureQueueHandler
 */
class PictureQueueHandler implements PictureQueueHandlerInterface
{
    /**
     * Create a new QueueHandler.
     *
     * @param QueueInterface $queue
     * @param string $queueName The queue used to push to.
     */
    public function __construct(
        protected QueueInterface $queue,
        protected string $queueName,
    ) {}
    
    /**
     * Handle the picture.
     *
     * @param string $path
     * @param string|ResourceInterface $resource
     * @param string|DefinitionInterface $definition
     * @param bool $regenerate
     * @param bool $uniqueJob
     * @return void
     */
    public function handle(
        string $path,
        string|ResourceInterface $resource,
        string|DefinitionInterface $definition,
        bool $regenerate = false,
        bool $uniqueJob = true,
    ): void {
        $job = new Job(
            name: PictureJobHandler::class,
            payload: [
                'path' => $path,
                'resource' => serialize($resource),
                'definition' => serialize($definition),
                'regenerate' => $regenerate,
            ],
        );

        $job->queue($this->queueName);
        
        if ($uniqueJob) {
            $definitionName = is_string($definition) ? $definition : $definition->name();
            $job->unique(id: $path.$definitionName);
        }
        
        $this->queue->push($job);
    }
}