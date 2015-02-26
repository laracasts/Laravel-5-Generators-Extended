<?php

namespace Laracasts\Generators;

class MigrationSchemaParser
{

    /**
     * Parse the migration schema.
     * Ex: name:string, age:integer:nullable
     *
     * @param  string $schema
     * @return array
     */
    public function parse($schema)
    {
        $fields = $this->getFields($schema);
        $schema = [];

        foreach ($fields as $field) {
            $segments = $this->getDetails($field);

            $schema[] = $segments;
        }

        return $schema;
    }

    /**
     * Get an array of fields from the given schema.
     *
     * @param  string $schema
     * @return array
     */
    private function getFields($schema)
    {
        return preg_split('/\s?,\s?/', $schema);
    }

    /**
     * Get the details of the schema field.
     *
     * @param  string $field
     * @return array
     */
    private function getDetails($field)
    {
        $segments = explode(':', $field);

        return [
            'name' => array_shift($segments),
            'type' => array_shift($segments),
            'options' => $this->parseOptions($segments)
        ];
    }

    /**
     * Parse any given options into something usable.
     *
     * @param  array $options
     * @return array
     */
    private function parseOptions($options)
    {
        if (empty($options)) return [];

        foreach ($options as $option) {
            if (str_contains($option, '(')) {
                preg_match('/([a-z]+)\(([^\)]+)\)/i', $option, $matches);

                $results[$matches[1]] = $matches[2];
            } else {
                $results[$option] = true;
            }
        }

        return $results;
    }
}
