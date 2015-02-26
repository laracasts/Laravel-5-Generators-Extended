<?php

namespace Laracasts\Generators;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MigrationMakeCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration class';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Meta information for the requested migration.
     *
     * @var array
     */
    protected $meta;

    /**
     * @var MigrationNameParser
     */
    private $parser;

    /**
     * Create a new command instance.
     *
     * @param Filesystem          $files
     * @param MigrationNameParser $parser
     */
    public function __construct(Filesystem $files, MigrationNameParser $parser)
    {
        parent::__construct();

        $this->files = $files;
        $this->parser = $parser;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $name = $this->argument('name');

        if ($this->files->exists($path = $this->getPath($name)))
        {
            return $this->error($this->type.' already exists!');
        }

        $this->meta = $this->parser->parse($name);

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileMigrationStub());

        $this->info('Migration created successfully.');
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if ( ! $this->files->isDirectory(dirname($path)))
        {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Get the path to where we should store the migration.
     *
     * @param  string $name
     * @return string
     */
    private function getPath($name)
    {
        return './database/migrations/'.date('Y_m_d_His').'_'.$name.'.php';
    }

    /**
     * Compile the migration stub.
     *
     * @return string
     */
    private function compileMigrationStub()
    {
        $stub = $this->files->get(__DIR__.'/stubs/migration.stub');

        $this->replaceClassName($stub)
             ->replaceSchema($stub)
             ->replaceTableName($stub);

        return $stub;
    }

    /**
     * Replace the class name in the stub.
     *
     * @param  string $stub
     * @return $this
     */
    private function replaceClassName(&$stub)
    {
        $className = ucwords(camel_case($this->argument('name')));

        $stub = str_replace('{{class}}', $className, $stub);

        return $this;
    }

    /**
     * Replace the table name in the stub.
     *
     * @param  string $stub
     * @return $this
     */
    public function replaceTableName(&$stub)
    {
        $table = $this->meta['table'];

        $stub = str_replace('{{table}}', $table, $stub);

        return $this;
    }

    /**
     * Replace the schema for the stub.
     *
     * @param  string $stub
     * @return $this
     */
    public function replaceSchema(&$stub)
    {
        $schema = (new MigrationSchemaParser)->parse($this->option('schema'));
        $schema = (new SchemaSyntaxCreator)->create($schema, $this->meta);

        $stub = str_replace(['{{schema_up}}', '{{schema_down}}'], $schema, $stub);

        return $this;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the migration'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['schema', 's', InputOption::VALUE_OPTIONAL, 'Optional schema to be attached to the migration', null],
        ];
    }

}
