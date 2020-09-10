<?php

namespace spec\Laracasts\Generators\Migrations;

use PhpSpec\ObjectBehavior;

class SyntaxBuilderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Laracasts\Generators\Migrations\SyntaxBuilder');
    }

    function it_creates_the_php_syntax_for_the_schema()
    {
        $schema = [[
            "name" => "email",
            "type" => "string",
            "arguments" => ["100"],
            "options" => [
                "unique" => true,
                "nullable" => true,
                "default" => '"foo@example.com"'
            ]
        ]];

        $this->create($schema, ['table' => 'posts', 'action' => 'create'])['up']->shouldBe(getStub());
        $this->create($schema, ['table' => 'posts', 'action' => 'create'])['down']->shouldBe("Schema::dropIfExists('posts');");
    }

    function it_doesnt_add_duplicate_id_field()
    {
        $schema = [
            [
                'name' => 'id',
                'type' => 'increments',
                'arguments' => [],
                'options' => [],
            ], [
                "name" => "email",
                "type" => "string",
                "arguments" => ["100"],
                "options" => [
                    "unique" => true,
                    "nullable" => true,
                    "default" => '"foo@example.com"'
                ]
            ]
        ];

        $this->create($schema, ['table' => 'posts', 'action' => 'create'])['up']->shouldBe(getStub());
    }

    function it_doesnt_add_duplicate_timestamp_fields()
    {
        $schema = [[
            'name' => 'created_at',
            'type' => 'date',
            'arguments' => [],
            'options' => [],
        ]];

        $this->create($schema, ['table' => 'posts', 'action' => 'create'])['up']->shouldBe(getTimestampStub());
    }

}

function getStub()
{
    return <<<EOT
Schema::create('{{table}}', function (Blueprint \$table) {
            \$table->increments('id');
            \$table->string('email', 100)->unique()->nullable()->default("foo@example.com");
            \$table->timestamps();
        });
EOT;
}

function getTimestampStub()
{
    return <<<EOT
Schema::create('{{table}}', function(Blueprint \$table) {
            \$table->increments('id');
            \$table->date('created_at');
        });
EOT;
}
