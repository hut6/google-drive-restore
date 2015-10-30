<?php
namespace Hut6\GoogleDrive;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentRestoreCommand extends GoogleCommand
{
    protected function configure()
    {
        $this->setName('restore')
            ->addArgument('file', InputArgument::REQUIRED, "File to import from")
            ->addOption('start', null, InputOption::VALUE_REQUIRED, "Starting item", 0)
            ->addOption('user', null, InputOption::VALUE_REQUIRED, "Only restore documents owned by user");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->restore($input, $output);
    }

    public function restore(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('magenta', null, array('bold'));
        $output->getFormatter()->setStyle('file', $style);
        $file = $input->getArgument('file');
        $total = trim(`cat $file | wc -l`);
        $count = 0;
        $actions = 0;
        $documentFile = fopen($file, 'r');
        $start = $input->getOption('start');
        while ($item = fgetcsv($documentFile, null, ',')) {
            try {
                if ($item[0] === 'time') {
                    continue;
                }
                $count++;
                if ($count < $start) {
                    fputs(STDERR, "Skipping $count\n");
                    continue;
                }
                $timestamp = $item[0];
                $timeAgo = (new \DateTime($timestamp))->diff(new \DateTime())->days . ' days ago';
                $actor = $item[1];
                $parentName = $item[3];
                $parentId = $item[4];
                $ownerEmailAddress = $item[5];
                $fileId = $item[6];
                $fileName = $item[7];
                $user = $input->getOption('user');
                if ($user && $ownerEmailAddress !== $user) {
                    $this->error($output, "<error>Only doing $user's files...</error>");
                    continue;
                }
                $output->writeln("\n<info>$actor</info> removed <file>$fileName</file> file <comment>$timeAgo</comment>");
                print("[$actions/$count/$total] Restore to \033[33m$parentName\033[0m (owner \033[36m$ownerEmailAddress\033[0m) [y/n/q]?");

                $response = strtolower(trim(fgets(STDIN)));
                if ($response === 'q') {
                    break;
                }
                if ($response !== 'y') {
                    continue;
                }

                $file = $this->restoreDocument($ownerEmailAddress, $fileId, $parentId);

                if (!$file) {
                    $this->error($output, "Unable to restore item");
                    continue;
                }
                $actions++;
            } catch (\Exception $e) {
                $this->error($output, $e->getMessage());
            }
        }
    }

    /**
     * @param $ownerEmailAddress
     * @param $fileId
     * @param $parentId
     * @return \Google_Service_Drive_DriveFile
     */
    protected function restoreDocument($ownerEmailAddress, $fileId, $parentId)
    {
        $client = $this->createClient($ownerEmailAddress);
        $drive = new \Google_Service_Drive($client);

        $file = $drive->files->get($fileId);
        if ($file->id !== $fileId) {
            fputs(STDERR, "\nFile ID didn't match\n");
        }
        if ($this->hasParent($file, $parentId)) {
            $this->done($fileId, $ownerEmailAddress);
            print "\033[1;33mAlready done\033[0m";

            return $file;
        }

        $file = new \Google_Service_Drive_DriveFile();
        $file = $drive->files->patch($fileId, $file, array('addParents' => "$parentId"));

        if ($this->hasParent($file, $parentId)) {
            $this->done($fileId, $ownerEmailAddress);
            print "\n\033[1;32mSuccess\033[0m";
        } else {
            print "\n\033[1;31mFailed\033[0m";
        }

        return $file;
    }

    private function hasParent(\Google_Service_Drive_DriveFile $file, $parentId)
    {
        $parents = $file->getParents();

        return isset($parents[0]) && $parents[0]['id'] === $parentId;
    }

    /**
     * @param $fileId
     * @param $ownerEmailAddress
     */
    protected function done($fileId, $ownerEmailAddress)
    {
        error_log($fileId . "\n", 3, "completed.$ownerEmailAddress.csv");
    }


}


    
    
    