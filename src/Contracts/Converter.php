<?php

namespace Meema\MediaConverter\Contracts;

interface Converter
{
    /**
     * Sets the path of the file input.
     *
     * @param  string  $path
     * @param  string|null  $bucket
     * @return \Meema\MediaConverter\Converters\MediaConvert
     */
    public function path(string $path, $bucket = null);

    /**
     * Sets the path & executes the job.
     *
     * @param  string  $s3Path
     * @param  string|null  $s3bucket
     * @return \Aws\Result
     */
    public function saveTo(string $s3Path, $s3bucket = null);

    /**
     * Cancels an active job.
     *
     * @param  string  $id
     * @return \Aws\Result
     */
    public function cancelJob(string $id);

    /**
     * Creates a new job based on the settings passed.
     *
     * @param  array  $settings
     * @param  array  $metaData
     * @param  int  $priority
     * @return \Aws\Result
     */
    public function createJob(array $settings, array $metaData, array $tags, int $priority);

    /**
     * Gets the job.
     *
     * @param  string  $id
     * @return \Aws\Result
     */
    public function getJob(string $id);

    /**
     * Lists all of the jobs based on your options provided.
     *
     * @param  array  $options
     * @return \Aws\Result
     */
    public function listJobs(array $options);
}
