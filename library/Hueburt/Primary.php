<?php

namespace Hueburt;

use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Primary extends Command {

    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;

    final protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('host', InputArgument::REQUIRED, 'The bridge host ip address'),
                new InputArgument('username', InputArgument::REQUIRED, 'The username registered at the host'),
                new InputOption('lights', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A set of lights (numeric) to control'),
            ))
        ;
        $this->extraConfig($this);
    }

    final protected function execute(InputInterface $input, OutputInterface $output)
    {  
        $client = new \Phue\Client($input->getArgument('host'), $input->getArgument('username'));
        $isAuthenticated = $client->sendCommand(new \Phue\Command\IsAuthorized());
        if (!$isAuthenticated) {
            throw new \Exception("Authentication failed, sorry!");
        }
        $this->input = $input;
        $this->output = $output;
        $this->play($client);
    }

    abstract public function extraConfig(Command $command);

    abstract public function play(\Phue\Client $client);

}