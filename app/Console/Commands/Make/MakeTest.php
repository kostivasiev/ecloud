<?php

namespace App\Console\Commands\Make;

use Illuminate\Foundation\Console\TestMakeCommand;

class MakeTest extends TestMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:test-custom {name : The name of the test class} {--unit : Create a unit test} {--pest : Create a pest test} {--path= : Create a test in path}';


    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        $path = $this->option('path');
        if (!is_null($path)) {
            if ($path) {
                return $rootNamespace. '\\' . $path;
            }

            return $rootNamespace;
        }

        if ($this->option('unit')) {
            return $rootNamespace.'\Unit';
        }

        return $rootNamespace.'\Feature';
    }
}
