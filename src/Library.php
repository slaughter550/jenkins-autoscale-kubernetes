<?php

namespace App;

use Maknz\Slack\Client;

class Library
{
    private static $data = null;

    private static $holdingFile = 'jenkins-working';

    public static function canRun(): bool
    {
        $statusFile = __DIR__ . self::$holdingFile;
        if (file_exists($statusFile)) {
            return false;
        }
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

    public static function report()
    {
        self::Slack()->from('Jenkins Ops')->to('#dev-pulse')->attach([
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
        return new Client('https://hooks.slack.com/services/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');
    }

    private static function getData($param): float
    {
        if (self::$data === null) {
            self::$data = json_decode(file_get_contents('/tmp/jenkins-output.json'), true);
        }

        return round($data[$param]['sec10']['latest'], 1);
    }
}
