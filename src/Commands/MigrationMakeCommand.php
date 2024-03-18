<?php

namespace Laracasts\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laracasts\Generators\Migrations\NameParser;
use Laracasts\Generators\Migrations\SchemaParser;
use Laracasts\Generators\Migrations\SyntaxBuilder;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MigrationMakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:migration:schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration class and apply schema at the same time';

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
     * @var Composer
     */
    private $composer;
    protected array $rawSchema;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = app()['composer'];
    }

    /**
     * Alias for the fire method.
     *
     * In Laravel 5.5 the fire() method has been renamed to handle().
     * This alias provides support for both Laravel 5.4 and 5.5.
     */
    public function handle()
    {
        $this->fire();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->meta = (new NameParser)->parse($this->argument('name'));

        $this->makeMigration();
        $this->makeModel();
        
        $this->composer->dumpAutoloads();
    }

    /**
     * Get the application namespace.
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }

    /**
     * Generate the desired migration.
     */
    protected function makeMigration()
    {
        $name = $this->argument('name');

        if ($this->files->exists($path = $this->getPath($name))) {
            return $this->error($this->type . ' already exists!');
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileMigrationStub());

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $this->line("<info>Created Migration:</info> {$filename}");
    }


    protected static function quotedString($value): string {
        return "'" . $value . "'";
    }

    /**
     * Generate a string representing an array of values
     * (a quick and dirty shorthand `var_dump` without proper escaping)
     * @param string[] $values
     * @return string
     */
    protected function quotedValues($values): string {
        $quoted_values = array_map([__CLASS__, 'quotedString'], $values);
        return '[' . implode(', ', $quoted_values) . ']';
    }

    /**
     * Get any obvious model casts (datetime/timestamp fields)
     * @return string
     */
    protected function getCasts(): string {
        $castFields = array_filter($this->rawSchema, function ($column) {
            return Str::contains(Str::lower($column['type']), ['datetime', 'timestamp']);
        });

        if (empty($castFields))
            return '';

        $casts = "\n    protected \$casts = [";
        foreach ($castFields as $column) {
            $column = (object)$column;
            $casts .= "\n        '$column->name' => 'datetime',\n";
        }
        $casts .= "    ];\n";

        return $casts;
    }

    /**
     * Generate an Eloquent model, if the user wishes.
     */
    protected function makeModel()
    {
        $modelName = $this->getModelName();
        $modelPath = $this->getModelPath($modelName);

        if ($this->option('model')) {
            if (!$this->files->exists($modelPath)) {
                $this->call('make:model', [
                    'name' => $modelName
                ]);
            }

            // A really nasty hack to populate the fillable model property, will operate on existing models if no
            // $fillable var is set, though probably not smart enough to properly handle a add_columns_to_table
            // request
            if ($this->files->exists($modelPath)) {
                $columnNames = array_map(function ($column) {
                    return $column['name'];
                }, $this->rawSchema);

                if (!empty($columnNames)) {
                    $fillable = "\n    protected \$fillable = " . $this->quotedValues($columnNames) . ";\n";
                    $casts = $this->getCasts();

                    /** @noinspection PhpUnhandledExceptionInspection */
                    $contents = $this->files->get($modelPath);
                    if (!Str::contains($contents, '$fillable')) {
                        $contents = Str::replaceLast("}", $fillable . $casts, $contents) . "}\n";
                        $this->files->put($modelPath, $contents);
                    }
                }
            }
        }
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Get the path to where we should store the migration.
     *
     * @param  string $name
     * @return string
     */
    protected function getPath($name)
    {
        $path = ($this->option('path'))
            ? base_path().$this->option('path').'/'.date('Y_m_d_His').'_'.$name.'.php'
            : base_path().'/database/migrations/'.date('Y_m_d_His').'_'.$name.'.php';

        return $path;
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     * @return string
     */
    protected function getModelPath($name)
    {
        $name = str_replace($this->getAppNamespace(), '', $name);

        // Not sure why Models/ wasn't already included in the model path
        return $this->laravel['path'] . '/Models/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Compile the migration stub.
     *
     * @return string
     */
    protected function compileMigrationStub()
    {
        $stub = $this->files->get(__DIR__ . '/../stubs/migration.stub');

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
    protected function replaceClassName(&$stub)
    {
        $className = ucwords(Str::camel($this->argument('name')));

        $stub = str_replace('{{class}}', $className, $stub);

        return $this;
    }

    /**
     * Replace the table name in the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceTableName(&$stub)
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
    protected function replaceSchema(&$stub)
    {
        if ($schema = $this->option('schema')) {
            $schema = (new SchemaParser)->parse($schema);
            // Save a copy of the rawSchema to generate Model $fillable/$casts
            $this->rawSchema = $schema;
        }

        $schema = (new SyntaxBuilder)->create($schema, $this->meta);

        $stub = str_replace(['{{schema_up}}', '{{schema_down}}'], $schema, $stub);

        return $this;
    }

    /**
     * Get the class name for the Eloquent model generator.
     *
     * @return string
     */
    protected function getModelName()
    {
        return ucwords(Str::singular(Str::camel($this->meta['table'])));
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
            ['model', null, InputOption::VALUE_OPTIONAL, 'Want a model for this table?', false],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Optional path for a migration.', false],
        ];
    }
}
