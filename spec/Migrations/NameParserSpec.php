<?php

namespace spec\Laracasts\Generators\Migrations;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NameParserSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Laracasts\Generators\Migrations\NameParser');
    }

    function it_parses_a_migration_name_into_an_array()
    {
        $this->parse('create_posts_table')->shouldReturn([
            'action' => 'create',
            'table' => 'posts'
        ]);
    }

    function it_parses_a_migration_name_where_the_table_is_two_words()
    {
        $this->parse('create_yearly_reports_table')->shouldReturn([
            'action' => 'create',
            'table' => 'yearly_reports'
        ]);
    }

    function it_parses_a_complex_migration_name()
    {
        $this->parse('add_user_id_to_reports_table')->shouldReturn([
            'action' => 'add',
            'table' => 'reports'
        ]);
    }
}
