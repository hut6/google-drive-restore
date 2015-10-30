<?php
/**
 * @author Ryan Castle <ryan@dwd.com.au>
 * @since 30/10/2015
 */

namespace Hut6\GoogleDrive;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GoogleCommand extends Command
{
    protected $clients = array();

    /**
     * @param string $actor
     * @return \Google_Auth_AssertionCredentials
     */
    protected function buildCredentials($actor)
    {
        $privateKeyPath = __DIR__ . '/../../credentials.json';
        if (!is_readable($privateKeyPath)) {
            throw new \RuntimeException("Server must be fully configured to remove this item. Add 'credentials.json' to root directory.");
        }
        $credentials = json_decode(file_get_contents($privateKeyPath), true);

        return new \Google_Auth_AssertionCredentials(
            $credentials['client_email'],//'187240088370-lgp72dkqarme9h5l1b8n6oabi0juitgn@developer.gserviceaccount.com',
            array(
                'https://www.googleapis.com/auth/drive',
                'https://www.googleapis.com/auth/admin.reports.audit.readonly',
                'https://www.googleapis.com/auth/admin.reports.usage.readonly'
            ),
            $credentials['private_key'],//file_get_contents($privateKeyPath),
            'notasecret',
            'http://oauth.net/grant_type/jwt/1.0/bearer',
            $actor
        );
    }

    /**
     * @param string $ownerEmailAddress
     * @return \Google_Client
     */
    protected function createClient($ownerEmailAddress)
    {
        if (empty($this->clients[$ownerEmailAddress])) {
            $client = new \Google_Client();
            $client->setAssertionCredentials($this->buildCredentials($ownerEmailAddress));
            $this->clients[$ownerEmailAddress] = $client;
        } else {
            $client = $this->clients[$ownerEmailAddress];
        }

        /** @var \Google_Auth_OAuth2 $auth */
        $auth = $client->getAuth();
        if ($auth->isAccessTokenExpired()) {
            $auth->refreshTokenWithAssertion();
        }

        return $client;
    }

    /**
     * @param OutputInterface $output
     * @param $message
     * @return bool
     */
    protected function error(OutputInterface $output, $message)
    {
        return $output instanceof ConsoleOutputInterface && $output->getErrorOutput()->writeln($message);
    }
}