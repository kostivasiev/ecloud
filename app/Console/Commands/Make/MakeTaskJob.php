<?php

namespace App\Console\Commands\Make;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class MakeTaskJob extends GeneratorCommand
{
    use CreatesMatchingTest;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:task-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new task job {name} {resource?} --test';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'class';

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        return str_replace('{{ name }}', $this->argument('name'), $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return 'stubs/TaskJob.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        if (!empty($this->getResourceInput())) {
            return $rootNamespace . '\Jobs\\' .  $this->getResourceInput();
        }

        return $rootNamespace . '\Jobs';
    }

    protected function getResourceInput()
    {
        return trim($this->argument('resource'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the task job.'],
            ['resource', InputArgument::OPTIONAL, 'The name of the resource.'],
        ];
    }

    /**
     * Create the matching test case if requested.
     *
     * @param  string  $path
     * @return void
     */
    protected function handleTestCreation($path)
    {
        if (! $this->option('test') && ! $this->option('pest')) {
            return;
        }

        $this->call('make:test-custom', [
            'name' => Str::of($path)->after($this->laravel['path'])->beforeLast('.php')->append('Test')->replace('\\', '/'),
            '--path' => 'Unit',
        ]);
    }
}
