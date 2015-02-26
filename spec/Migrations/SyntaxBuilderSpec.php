<?php

namespace spec\Laracasts\Generators\Migrations;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SyntaxBuilderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Laracasts\Generators\Migrations\SyntaxBuilder');
    }

    function it_creates_the_php_syntax_for_the_schema()
    {
        $schema = [[
            "name"    => "email",
            "type"    => "string",
            "options" => [
                "unique"   => true,
                "nullable" => true
            ]
        ]];

        $desiredSyntax = "\$table->string('email')->unique()->nullable();";

        //$this->create($schema)->shouldBe($desiredSyntax);
        $this->create($schema, ['table' => 'posts', 'action' => 'create'])->shouldBeArray();
    }

}
