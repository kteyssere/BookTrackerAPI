<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\WebSocketChat\WebSocketServer;

class WebSocketServerCommand extends Command
{
    protected static $defaultName = 'websocket:serve';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = new WebSocketServer();
        $server->run();

        return Command::SUCCESS;
    }
}