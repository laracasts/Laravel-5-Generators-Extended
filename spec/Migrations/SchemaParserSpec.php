<?php

namespace spec\Laracasts\Generators\Migrations;

use PhpSpec\ObjectBehavior;

class SchemaParserSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Laracasts\Generators\Migrations\SchemaParser');
    }

    function it_parses_a_basic_string_schema()
    {
        $this->parse('name:string')->shouldReturn([
            ['name' => 'name', 'type' => 'string', 'arguments' => [], 'options' => []]
        ]);
    }

    function it_parses_schema_with_multiple_fields()
    {
        $this->parse('name:string, age:integer')->shouldReturn([
            ['name' => 'name', 'type' => 'string', 'arguments' => [], 'options' => []],
            ['name' => 'age', 'type' => 'integer', 'arguments' => [], 'options' => []],
        ]);
    }

    function it_parses_schema_that_includes_extras()
    {
        $this->parse('age:integer:nullable:default(21)')->shouldReturn([
            ['name' => 'age', 'type' => 'integer', 'arguments' => [], 'options' => ['nullable' => true, 'default' => '21']]
        ]);
    }

    function it_parses_correctly_when_the_type_contains_method_arguments()
    {
        $this->parse('amount:decimal(5,2)')->shouldReturn([
            ['name' => 'amount', 'type' => 'decimal', 'arguments' => ['5', '2'], 'options' => []]
        ]);
    }

    function it_parses_schema_with_multiple_fields_using_no_spaces()
    {
        $this->parse('name:string,amount:decimal(5,2)')->shouldReturn([
            ['name' => 'name', 'type' => 'string', 'arguments' => [], 'options' => []],
            ['name' => 'amount', 'type' => 'decimal', 'arguments' => ['5', '2'], 'options' => []]
        ]);
    }

    function it_parses_schema_fields_that_want_foreign_constraints()
    {
        $this->parse('user_id:integer:foreign')->shouldReturn([
            ['name' => 'user_id', 'type' => 'integer', 'arguments' => [], 'options' => []],
            ['name' => 'user_id', 'type' => 'foreign', 'arguments' => [], 'options' => ['references' => "'id'", 'on' => "'users'"]]
        ]);
    }
}
