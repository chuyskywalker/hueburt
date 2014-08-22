<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Phue\Client;
use Phue\Light;

$console = new Application();

$console
    ->register('blood')
    ->setDefinition(array(
        new InputArgument('host', InputArgument::REQUIRED, 'The bridge host ip address'),
        new InputArgument('username', InputArgument::REQUIRED, 'The username registered at the host'),
        new InputArgument('hue', InputArgument::REQUIRED, 'The "Hue" to pulse at (color) 0-65535'),
        new InputOption('lights', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A set of lights (numeric) to control'),
        new InputOption('sat', 's', InputOption::VALUE_REQUIRED, 'Light saturation', 255),
        new InputOption('bri', 'b', InputOption::VALUE_REQUIRED, 'Light brightness', 255),
        new InputOption('delay', 'd', InputOption::VALUE_REQUIRED, 'Delay between pulses', 5),
    ))
    ->setDescription('Pulse lights on a specific color')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $lights = $input->getOption('lights');
        if (count($lights) == 0){
            throw new Exception("You must provide -l light options");
        }
        $client = new Client($input->getArgument('host'), $input->getArgument('username'));

        $isAuthenticated = $client->sendCommand(
            new \Phue\Command\IsAuthorized
        );

        if (!$isAuthenticated) {
            throw new Exception("Authentication failed, sorry!");
        }

        /** @var Light[] $lightSet */
        $lightSet = $client->getLights();

        $output->writeln('Setting up');
        // initial setup
        foreach ($lights as $lightId) {
            if (!array_key_exists($lightId, $lightSet)) {
                throw new Exception("The light [$lightId] did not exist");
            }
            $lightInstance = $lightSet[$lightId];
            $command = new \Phue\Command\SetLightState($lightInstance);
            $command
                ->brightness($input->getOption('bri'))
                ->hue($input->getArgument('hue'))
                ->saturation($input->getOption('sat'))
                ->transitionTime(3)
            ;
            $client->sendCommand($command);
        }

        // wait for it...
        sleep(3);

        $output->writeln('pulsing');
        // pulse!
        $onOff = $input->getOption('bri');
        while(1) {
            $onOff = ($onOff == 0) ? $input->getOption('bri') : 0;
            $output->writeln("Pulsing to $onOff");
            foreach ($lights as $lightId) {
                $command = new \Phue\Command\SetLightState($lightSet[$lightId]);
                $command->brightness($onOff)->transitionTime($input->getOption('delay'))->send($client);
            }
            usleep(($input->getOption('delay')*1000000)+250000);
        }

    })
;

$console->run();
