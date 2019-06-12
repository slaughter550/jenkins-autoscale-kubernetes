<?php

require __DIR__ . '/vendor/autoload.php';

use App\Library;

if (! Library::canRun()) {
    exit('There is already a thread running');
}

$clusterName = "pdx1b-build";
$templateName = "pdx1b-build-n1-standard8";

$num = null;
$workers = Library::getNumWorkers();
$queueLength = Library::getQueueLength();
$busyExecutors = Library::getBusyExecutors();
$onlineExecutors = Library::getOnlineExecutors();

$status = "Nodes: $workers - Online: $onlineExecutors - Busy: $busyExecutors - Queue: $queueLength";
echo($status);

if ($workers > 0 && $queueLength == 0 && $busyExecutors == 0) {
    $num = 0;
} elseif ($busyExecutors >= $onlineExecutors && $queueLength > 3) {
    $num = $workers + 1;
} elseif ($queueLength > 8) {
    $num = $workers + 2;
} elseif ($workers == 0 && $queueLength > 0) {
    $num = 1;
}

if ($num !== null && $num <= 16) {
    $this->report($workers, $onlineExecutors, $busyExecutors, $queueLength);
    try {
        touch($statusFile);
        shell_exec("gcloud container clusters resize $clusterName --node-pool $templateName --size $num");

        $old = $workers . " " . str_plural('worker', $workers);
        $new = $num . " " . str_plural('worker', $num);

        $message = "\n[Auto] Changed Jenkins from $old => $new";
        Library::Slack()->from('Jenkins Ops')->to('#dev-jenkins')->send($message);
    } catch (Exception $e) {
        Library::Slack()->from('Jenkins Ops')->to('#dev-jenkins')->send($e->getMessage());
    } finally {
        @unlink($statusFile);
    }
}
