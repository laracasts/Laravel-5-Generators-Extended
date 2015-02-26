<?php

namespace spec\Laracasts\Generators;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MigrationSchemaParserSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Laracasts\Generators\MigrationSchemaParser');
    }

    function it_parses_a_basic_string_schema()
    {
        $this->parse('name:string')->shouldReturn([
            ['name' => 'name', 'type' => 'string', 'options' => []]
        ]);
    }

    function it_parses_schema_with_multiple_fields()
    {
        $this->parse('name:string, age:integer')->shouldReturn([
            ['name' => 'name', 'type' => 'string', 'options' => []],
            ['name' => 'age', 'type' => 'integer', 'options' => []],
        ]);
    }

    function it_parses_schema_that_includes_extras()
    {
        $this->parse('age:integer:nullable:default(21)')->shouldReturn([
            ['name' => 'age', 'type' => 'integer', 'options' => ['nullable' => true, 'default' => '21']]
        ]);
    }
}
