<?php
/**
 * @author Ryan Castle <ryan@dwd.com.au>
 * @since 29/10/2015
 */

namespace GoogleDrive;

require __DIR__.'/vendor/autoload.php';

use Hut6\GoogleDrive\DocumentRestoreCommand;
use Hut6\GoogleDrive\QueryReportsCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new DocumentRestoreCommand());
$application->add(new QueryReportsCommand());
$application->run();