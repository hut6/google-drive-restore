<?php
/**
 * @author Ryan Castle <ryan@dwd.com.au>
 * @since 29/10/2015
 */

namespace Hut6\GoogleDrive;


use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueryReportsCommand extends GoogleCommand
{
    public function configure()
    {
        $this->setName("query")
            ->addArgument('admin', InputArgument::REQUIRED, "Admin user for service account")
            ->addOption('user', null, InputOption::VALUE_REQUIRED, "Restrict events to user", 'all')
            ->addOption('json', null, InputOption::VALUE_REQUIRED, "Output JSON file", 'reports/activity.json')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, "Start date of query", '2015-10-24T00:00:00.000Z')
            ->addOption('event', null, InputOption::VALUE_REQUIRED, "Google Drive event name ('remove_from_folder' etc)")
            ->addOption('end', null, InputOption::VALUE_REQUIRED, "End date of query", null);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $actor = $input->getOption('user');
        $client = $this->createClient($input->getArgument('admin'));
        $reports = new \Google_Service_Reports($client);
        $optParams = array(
            'fields' => 'items(id,actor/email,events(name,parameters)),nextPageToken',
            'startTime' => $input->getOption('start')
        );
        if($input->getOption('end')) {
            $optParams['endTime'] = $input->getOption('end');
        }
        if($input->getOption('event')) {
            $optParams['eventName'] = $input->getOption('event');
        }
        $items = array();
        $csv = fopen('php://stdout', 'w+');
        do {
            $request = $reports->activities->listActivities($actor, 'drive', $optParams);
            /** @var \Google_Service_Reports_Activity $requestItem */
            foreach ($request->getItems() as $requestItem) {
                $item = $requestItem->toSimpleObject();
                $activity = array('time' => $item->id['time'], 'actor' => $item->actor['email'], 'event' => $item->events[0]['name']);
                foreach ($item->events[0]['parameters'] as $parameter) {
                    if ($parameter['name'] === 'source_folder_id') {
                        $activity['source_folder_id'] = $parameter['multiValue'][0];
                    } elseif ($parameter['name'] === 'source_folder_title') {
                        $activity['source_folder_title'] = $parameter['multiValue'][0];
                    } elseif ($parameter['name'] === 'owner') {
                        $activity['owner'] = $parameter['value'];
                    } elseif ($parameter['name'] === 'doc_id') {
                        $activity['doc_id'] = $parameter['value'];
                    } elseif ($parameter['name'] === 'doc_title') {
                        $activity['doc_title'] = $parameter['value'];
                    }
                }
                if (sizeof($items) === 0) {
                    fputcsv($csv, array_keys($activity));
                }
                fputcsv($csv, $activity);
                $items[md5(json_encode($activity))] = $activity;
            }
        } while ($optParams['pageToken'] = $request->getNextPageToken());

        file_put_contents($input->getOption('json'), json_encode($items, JSON_PRETTY_PRINT));
        fclose($csv);
    }
}