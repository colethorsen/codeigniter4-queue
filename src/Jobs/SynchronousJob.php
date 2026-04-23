<?php namespace CodeIgniter\Queue\Jobs;

/**
 * Marker interface for jobs that should execute synchronously.
 *
 * When a Job class implements this interface, Job::dispatch() will
 * call handle() in-process instead of storing to the queue, unless
 * a future delay has been explicitly set via delay() or delayUntil().
 */
interface SynchronousJob
{
}
