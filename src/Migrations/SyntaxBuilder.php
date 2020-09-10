<?php

namespace Laracasts\Generators\Migrations;

use Laracasts\Generators\GeneratorException;

class SyntaxBuilder
{
    /**
     * A template to be inserted.
     *
     * @var string
     */
    private $template;

    /**
     * Create the PHP syntax for the given schema.
     *
     * @param  array $schema
     * @param  array $meta
     * @return string
     * @throws GeneratorException
     */
    public function create($schema, $meta)
    {
        $up = $this->createSchemaForUpMethod($schema, $meta);
        $down = $this->createSchemaForDownMethod($schema, $meta);

        return compact('up', 'down');
    }

    /**
     * Create the schema for the "up" method.
     *
     * @param  string $schema
     * @param  array $meta
     * @return string
     * @throws GeneratorException
     */
    private function createSchemaForUpMethod($schema, $meta)
    {
        $fields = $this->constructSchema($schema);

        if ($meta['action'] == 'create') {
            return $this->insert($fields)->into($this->getCreateSchemaWrapper());
        }

        if ($meta['action'] == 'add') {
            return $this->insert($fields)->into($this->getChangeSchemaWrapper());
        }

        if ($meta['action'] == 'remove') {
            $fields = $this->constructSchema($schema, 'Drop');

            return $this->insert($fields)->into($this->getChangeSchemaWrapper());
        }

        // Otherwise, we have no idea how to proceed.
        throw new GeneratorException;
    }

    /**
     * Construct the syntax for a down field.
     *
     * @param  array $schema
     * @param  array $meta
     * @return string
     * @throws GeneratorException
     */
    private function createSchemaForDownMethod($schema, $meta)
    {
        // If the user created a table, then for the down
        // method, we should drop it.
        if ($meta['action'] == 'create') {
            return sprintf("Schema::dropIfExists('%s');", $meta['table']);
        }

        // If the user added columns to a table, then for
        // the down method, we should remove them.
        if ($meta['action'] == 'add') {
            $fields = $this->constructSchema($schema, 'Drop');

            return $this->insert($fields)->into($this->getChangeSchemaWrapper());
        }

        // If the user removed columns from a table, then for
        // the down method, we should add them back on.
        if ($meta['action'] == 'remove') {
            $fields = $this->constructSchema($schema);

            return $this->insert($fields)->into($this->getChangeSchemaWrapper());
        }

        // Otherwise, we have no idea how to proceed.
        throw new GeneratorException;
    }

    /**
     * Store the given template, to be inserted somewhere.
     *
     * @param  string $template
     * @return $this
     */
    private function insert($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get the stored template, and insert into the given wrapper.
     *
     * @param  string $wrapper
     * @param  string $placeholder
     * @return mixed
     */
    private function into($wrapper, $placeholder = 'schema_up')
    {
        return str_replace('{{' . $placeholder . '}}', $this->template, $wrapper);
    }

    /**
     * Get the wrapper template for a "create" action.
     *
     * @return string
     */
    private function getCreateSchemaWrapper()
    {
        return file_get_contents(__DIR__ . '/../stubs/schema-create.stub');
    }

    /**
     * Get the wrapper template for an "add" action.
     *
     * @return string
     */
    private function getChangeSchemaWrapper()
    {
        return file_get_contents(__DIR__ . '/../stubs/schema-change.stub');
    }

    /**
     * Construct the schema fields.
     *
     * @param  array $schema
     * @param  string $direction
     * @return array
     */
    private function constructSchema($schema, $direction = 'Add')
    {
        if (!$schema) return '';

        if (!$this->hasDefinedIdColumn($schema)) {
            $schema = $this->addIdColumn($schema);
        }

        if (!$this->hasDefinedTimestamps($schema)) {
            $schema = $this->addTimestamps($schema);
        }

        $fields = array_map(function ($field) use ($direction) {
            $method = "{$direction}Column";

            return $this->$method($field);
        }, $schema);

        return implode("\n" . str_repeat(' ', 12), $fields);
    }


    /**
     * Construct the syntax to add a column.
     *
     * @param  string $field
     * @return string
     */
    private function addColumn($field)
    {
        $syntax = sprintf("\$table->%s(%s)", $field['type'], empty($field['name']) ? '' : "'$field[name]'");

        // If there are arguments for the schema type, like decimal('amount', 5, 2)
        // then we have to remember to work those in.
        if ($field['arguments']) {
            $syntax = substr($syntax, 0, -1) . ', ';

            $syntax .= implode(', ', $field['arguments']) . ')';
        }

        foreach ($field['options'] as $method => $value) {
            $syntax .= sprintf("->%s(%s)", $method, $value === true ? '' : $value);
        }

        return $syntax .= ';';
    }

    /**
     * Construct the syntax to drop a column.
     *
     * @param  string $field
     * @return string
     */
    private function dropColumn($field)
    {
        return sprintf("\$table->dropColumn('%s');", $field['name']);
    }

    /**
     * Check to see if the user has already provided an id field
     *
     * @param array $schema
     *
     * @return bool
     */
    private function hasDefinedIdColumn(array $schema)
    {
        foreach ($schema as $definition) {
            if ($definition['name'] === 'id') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check to see if the user has already defined timestamp field(s)
     *
     * @param array $schema
     *
     * @return bool
     */
    private function hasDefinedTimestamps(array $schema)
    {
        $created_at = false;
        $updated_at = false;

        foreach ($schema as $definition) {
            if ($definition['name'] === 'created_at') {
                $created_at = true;
            } else if ($definition['name'] === 'updated_at') {
                $updated_at = true;
            }
        }

        return ($created_at || $updated_at);
    }

    /**
     * Adds an ID field to the beginning of the schema definition
     *
     * @param array $schema
     *
     * @return array
     */
    private function addIdColumn(array $schema)
    {
        return $this->appendFieldToSchema($schema, 'id', 'increments');
    }

    /**
     * Adds the timestamps option to the end of the schema definition
     *
     * @param array $schema
     *
     * @return array
     */
    private function addTimestamps(array $schema)
    {
        return $this->prependFieldToSchema($schema, '', 'timestamps');
    }

    /**
     * Adds a field to the start of the schema definition
     *
     * @param array  $schema
     * @param string $name
     * @param string $type
     * @param array  $arguments
     * @param array  $options
     *
     * @return array
     */
    private function appendFieldToSchema(array $schema, $name, $type, $arguments = [], $options = [])
    {
        array_unshift($schema, [
            'name' => $name,
            'type' => $type,
            'arguments' => $arguments,
            'options' => $options,
        ]);

        return $schema;
    }

    /**
     * Adds a field to the end of the schema definition
     *
     * @param array  $schema
     * @param string $name
     * @param string $type
     * @param array  $arguments
     * @param array  $options
     *
     * @return array
     */
    private function prependFieldToSchema(array $schema, $name, $type, $arguments = [], $options = [])
    {
        array_push($schema, [
            'name' => $name,
            'type' => $type,
            'arguments' => $arguments,
            'options' => $options,
        ]);

        return $schema;
    }
}
