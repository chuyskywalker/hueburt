<?php

namespace Hueburt;

use Phue\Command\SetLightState;
use Phue\Light;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Pulse extends Primary {

    public function extraConfig(Command $command) {
        $command
            ->setName('pulse')
            ->setDescription('Pulse lights on a specific color')
            ->addArgument('hue', InputArgument::REQUIRED, 'The "Hue" to pulse at (color) 0-65535')
            ->addOption('lights', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A set of lights (numeric) to control')
            ->addOption('sat', 's', InputOption::VALUE_REQUIRED, 'Light saturation', 255)
            ->addOption('bri', 'b', InputOption::VALUE_REQUIRED, 'Light brightness', 255)
            ->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'Delay between pulses', 5)
        ;
    }

    public function play(\Phue\Client $client) {

        $lights = $this->input->getOption('lights');
        if (count($lights) == 0){
            throw new \Exception("You must provide -l light options");
        }

        /** @var Light[] $lightSet */
        $lightSet = $client->getLights();

        $this->output->writeln('Setting up');
        // initial setup
        foreach ($lights as $lightId) {
            if (!array_key_exists($lightId, $lightSet)) {
                throw new \Exceptionn("The light [$lightId] did not exist");
            }
            $lightInstance = $lightSet[$lightId];
            $command = new SetLightState($lightInstance);
            $command
                ->brightness($this->input->getOption('bri'))
                ->hue($this->input->getArgument('hue'))
                ->saturation($this->input->getOption('sat'))
                ->transitionTime(3)
            ;
            $client->sendCommand($command);
        }

        // wait for it...
        sleep(3);

        $this->output->writeln('pulsing');
        // pulse!
        $onOff = $this->input->getOption('bri');
        while(1) {
            $onOff = ($onOff == 0) ? $this->input->getOption('bri') : 0;
            $this->output->writeln("Pulsing to $onOff");
            foreach ($lights as $lightId) {
                $command = new \Phue\Command\SetLightState($lightSet[$lightId]);
                $command->brightness($onOff)->transitionTime($this->input->getOption('delay'))->send($client);
            }
            usleep(($this->input->getOption('delay')*1000000)+250000);
        }

    }
}