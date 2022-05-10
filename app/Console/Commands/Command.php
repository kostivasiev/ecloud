<?php

namespace App\Console\Commands;

use Illuminate\Console\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (config('app.env') === 'production') {
            $testMode = $this->options()['test-run'] ?? false;
            $force = $this->options()['force'] ?? false;

            if ($force || (!$testMode && $this->confirm('Are you sure you want to run without test-run'))) {
                return parent::execute($input, $output);
            } elseif ($testMode) {
                return parent::execute($input, $output);
            }
        } else {
            return parent::execute($input, $output);
        }

        return self::FAILURE;
    }
}
