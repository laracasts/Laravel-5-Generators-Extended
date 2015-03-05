<?php

namespace Laracasts\Generators\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PivotMigrationMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:migration:pivot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration pivot class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Migration';

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
    }

    /**
     * Parse the name and format.
     *
     * @param  string $name
     * @return string
     */
    protected function parseName($name)
    {
        $tables = array_map('str_singular', $this->getSortedTableNames());
        $name = implode('', array_map('ucwords', $tables));

        return "Create{$name}PivotTable";
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../stubs/pivot.stub';
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     * @return string
     */
    protected function getPath($name = null)
    {
        return './database/migrations/' . date('Y_m_d_His') .
        '_create_' . $this->getPivotTableName() . '_pivot_table.php';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     */
    protected function buildClass($name = null)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replacePivotTableName($stub)
            ->replaceSchema($stub)
            ->replaceClass($stub, $name);
    }

    /**
     * Apply the name of the pivot table to the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replacePivotTableName(&$stub)
    {
        $stub = str_replace('{{pivotTableName}}', $this->getPivotTableName(), $stub);

        return $this;
    }

    /**
     * Apply the correct schema to the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceSchema(&$stub)
    {
        $tables = $this->getSortedTableNames();

        $stub = str_replace(
            ['{{columnOne}}', '{{columnTwo}}', '{{tableOne}}', '{{tableTwo}}'],
            array_merge(array_map('str_singular', $tables), $tables),
            $stub
        );

        return $this;
    }

    /**
     * Get the name of the pivot table.
     *
     * @return string
     */
    protected function getPivotTableName()
    {
        return implode('_', array_map('str_singular', $this->getSortedTableNames()));
    }

    /**
     * Sort the two tables in alphabetical order.
     *
     * @return array
     */
    protected function getSortedTableNames()
    {
        $tables = [
            strtolower($this->argument('tableOne')),
            strtolower($this->argument('tableTwo'))
        ];

        sort($tables);

        return $tables;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['tableOne', InputArgument::REQUIRED, 'The name of the first table.'],
            ['tableTwo', InputArgument::REQUIRED, 'The name of the second table.']
        ];
    }
}
