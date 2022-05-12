<?php

namespace Meema\MediaConverter\Converters;

use Aws\Credentials\Credentials;
use Aws\MediaConvert\MediaConvertClient;
use Aws\Result;
use Meema\MediaConverter\Contracts\Converter;

class MediaConvert implements Converter
{
    /**
     * The MediaConvert job's settings.
     *
     * @var array
     */
    public $jobSettings;
    /**
     * Client instance of AWS MediaConvert.
     *
     * @var MediaConvertClient
     */
    protected $client;

    /**
     * Construct converter.
     */
    public function __construct()
    {
        $config = config('media-converter');

        $this->jobSettings = (new $config['job_settings'])::get();

        $this->client = new MediaConvertClient([
            'version' => $config['version'],
            'region' => $config['region'],
            'credentials' => new Credentials($config['credentials']['key'], $config['credentials']['secret']),
            'endpoint' => $config['url'],
        ]);
    }

    /**
     * Get the MediaConvert Client.
     *
     * @return MediaConvertClient
     */
    public function getClient(): MediaConvertClient
    {
        return $this->client;
    }

    /**
     * Sets the path of the file input.
     *
     * @param string $path - represents the S3 path, e.g path/to/file.mp4
     * @param string|null $bucket - reference to the S3 Bucket name. Defaults to config value.
     * @return MediaConvert
     */
    public function path(string $path, $bucket = null): MediaConvert
    {
        $this->setFileInput($path, $bucket);

        return $this;
    }

    /**
     * Sets the S3 input file path.
     *
     * @param string $s3Path
     * @param string|null $s3bucket
     */
    protected function setFileInput(string $s3Path, ?string $s3bucket = null): void
    {
        $fileInput = sprintf(
            's3://%s/%s/%s',
            $s3bucket ?? config('filesystems.disks.s3.bucket'),
            trim(config('filesystems.disks.s3.root'), '/'),
            $s3Path
        );

        $this->jobSettings['Inputs'][0]['FileInput'] = $fileInput;
    }

    /**
     * Sets the S3 path & executes the job.
     *
     * @param string $s3Path
     * @param string|null $s3bucket
     * @return MediaConvert
     */
    public function saveTo(string $s3Path, $s3bucket = null): MediaConvert
    {
        $destination = sprintf(
            's3://%s/%s/%s',
            $s3bucket ?? config('filesystems.disks.s3.bucket'),
            trim(config('filesystems.disks.s3.root'), '/'),
            $s3Path
        );

        $this->jobSettings['OutputGroups'][0]['OutputGroupSettings']['FileGroupSettings']['Destination'] = $destination;

        return $this;
    }

    /**
     * Cancels an active job.
     *
     * @param string $id
     * @return Result
     */
    public function cancelJob(string $id): Result
    {
        return $this->client->cancelJob([
            'Id' => $id,
        ]);
    }

    /**
     * Creates a new job based on the settings passed.
     *
     * @param array $settings
     * @param array $metaData
     * @param int $priority
     * @return Result
     */
    public function createJob(array $settings = [], array $metaData = [], array $tags = [], int $priority = 0): Result
    {
        if (empty($settings)) {
            $settings = $this->jobSettings;
        }

        return $this->client->createJob([
            'Role' => config('media-converter.iam_arn'),
            'Settings' => $settings,
            'Queue' => config('media-converter.queue_arn'),
            'UserMetadata' => $metaData,
            'Tags' => $tags,
            'StatusUpdateInterval' => $this->getStatusUpdateInterval(),
            'Priority' => $priority,
        ]);
    }

    protected function getStatusUpdateInterval(): string
    {
        $webhookInterval = config('media-converter.webhook_interval');
        $allowedValues = [10, 12, 15, 20, 30, 60, 120, 180, 240, 300, 360, 420, 480, 540, 600];

        if (in_array($webhookInterval, [$allowedValues])) {
            return 'SECONDS_' . $webhookInterval;
        }

        return 'SECONDS_60'; // gracefully default to this value, in case the config value is missing or incorrect
    }

    /**
     * Gets the job.
     *
     * @param string $id
     * @return Result
     */
    public function getJob(string $id): Result
    {
        return $this->client->getJob([
            'Id' => $id,
        ]);
    }

    /**
     * Lists all of the jobs based on your options provided.
     *
     * @param array $options
     * @return Result
     */
    public function listJobs(array $options): Result
    {
        return $this->client->listJobs($options);
    }
}
