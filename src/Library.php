<?php

namespace App;

use Maknz\Slack\Client;

class Library
{
    private static $data = null;

    private static $holdingFile = 'jenkins-working';

    public static function canRun(): bool
    {
        return ! file_exists(self::getStatusFilePath());
    }

    public static function getBusyExecutors(): float
    {
        return self::getData('busyExecutors');
    }

    public static function getNumWorkers(): int
    {
        return intval(trim(shell_exec("gcloud container clusters describe pdx1b-build 2>&1 | grep currentNodeCount | grep -Eo '[0-9]{1,4}'")));
    }

    public static function getOnlineExecutors(): float
    {
        return self::getData('onlineExecutors');
    }

    public static function getQueueLength(): float
    {
        return self::getData('queueLength');
    }

    public static function getSetting($name)
    {
        $settings = require __DIR__ . '/../config/settings.php';

        return $settings['name'];
    }

    public static function getStatusFilePath()
    {
        return __DIR__ . self::$holdingFile;
    }

    public static function report()
    {
        self::Slack()->attach([
            'color' => 'good',
            'text' => "Online = Number of registered executors\nBusy = Number of executers with a job\nQueue = Number of jobs in the queue waiting for execution",
            'fields' => [
                [
                    'title' => 'Workers',
                    'value' => self::getNumWorkers(),
                    'short' => true,
                ],
                [
                    'title' => 'Online',
                    'value' => self::getOnlineExecutors(),
                    'short' => true,
                ],
                [
                    'title' => 'Busy',
                    'value' => self::getBusyExecutors(),
                    'short' => true,
                ],
                [
                    'title' => 'Queue',
                    'value' => self::getQueueLength(),
                    'short' => true,
                ],
            ],
        ])->send("Diagnostics");
    }

    public static function Slack()
    {
        $settings = self::getSetting('slack');

        $url = $settings['endpoint'];
        unset($settings['endpoint']);

        return new Client($url, $settings);
    }

    private static function getData($param): float
    {
        if (self::$data === null) {
            self::$data = json_decode(file_get_contents('/tmp/jenkins-output.json'), true);
        }

        return round(self::$data[$param]['sec10']['latest'], 1);
    }
}
