# Extended Migration Generators for Laravel 6, 7 and 8

[![Build Status](https://travis-ci.org/laracasts/Laravel-5-Generators-Extended.svg?branch=master)](https://travis-ci.org/laracasts/Laravel-5-Generators-Extended)

Easily define the migration schema right in your `make:migration` command. The new commands this package provides are:
- `make:migration:schema`
- `make:migration:pivot`

Which allows you to do `php artisan make:migration:schema create_dogs_table --schema="name:string:nullable, description:text, age:integer, email:string:unique"` and get a full migration that you can run using `php artisan migrate`. For simple cases like this one, no need to tinker inside the migration file itself. And if you do need to change anything, it's easier because the bulk of the code has already been generated.

Created in 2015 by [Jeffrey Way](https://github.com/jeffreyway) as a natural progression of his [JeffreyWay/Laravel-4-Generators](https://github.com/JeffreyWay/Laravel-4-Generators) package, to provide the same features for Laravel 5. Since 2017 it's been maintained by the [Backpack for Laravel](https://github.com/laravel-backpack/crud) team, with features and fixes added by community members like you. So feel free to pitch in.

![https://user-images.githubusercontent.com/1032474/92732702-cd8b3700-f344-11ea-8e3b-ae86501d66fe.gif](https://user-images.githubusercontent.com/1032474/92732702-cd8b3700-f344-11ea-8e3b-ae86501d66fe.gif)

## Table of Contents

  * [Versions](#versions)
  * [Installation](#installation)
  * [Examples](#examples)
    + [Migrations With Schema](#migrations-with-schema)
      - [Foreign Constraints](#foreign-constraints)
    + [Pivot Tables](#pivot-tables)

## Versions

Depending on your Laravel version, you should:
- use [JeffreyWay/Laravel-4-Generators](https://github.com/JeffreyWay/Laravel-4-Generators) for Laravel 4;
- use [`v1` of this package](https://github.com/laracasts/Laravel-5-Generators-Extended/tree/v1) for Laravel 5.0 - 5.8;
- use `v2` of this package for Laravel 6-8;

## Installation

You can install v2 of this project using composer, the service provider will be automatically loaded by Laravel itself:

```
composer require --dev laracasts/generators
```

You're all set. Run `php artisan` from the console, and you'll see the new commands in the `make:*` namespace section.


## Examples

- [Migrations With Schema](#migrations-with-schema)
- [Pivot Tables](#pivot-tables)

### Migrations With Schema

```
php artisan make:migration:schema create_users_table --schema="username:string, email:string:unique"
```

Notice the format that we use, when declaring any applicable schema: a comma-separated list...

```
COLUMN_NAME:COLUMN_TYPE
```

So any of these will do:

```
username:string
body:text
age:integer
published_at:date
excerpt:text:nullable
email:string:unique:default('foo@example.com')
```

Using the schema from earlier...

```
--schema="username:string, email:string:unique"
```

...this will give you:

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table) {
			$table->increments('id');
			$table->string('username');
			$table->string('email')->unique();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}
```

When generating migrations with schema, the name of your migration (like, "create_users_table") matters. We use it to figure out what you're trying to accomplish. In this case, we began with the "create" keyword, which signals that we want to create a
new table.

Alternatively, we can use the "remove" or "add" keywords, and the generated boilerplate will adapt, as needed. Let's create a migration to remove a column.

```
php artisan make:migration:schema remove_user_id_from_posts_table --schema="user_id:integer"
```

Now, notice that we're using the correct Schema methods.

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUserIdFromPostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('posts', function(Blueprint $table) {
			$table->dropColumn('user_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('posts', function(Blueprint $table) {
			$table->integer('user_id');
		});
	}

}
```

Here's a few other examples of commands that you might write:

- `php artisan make:migration:schema create_posts_table`
- `php artisan make:migration:schema create_posts_table --schema="title:string, body:text, excerpt:string:nullable"`
- `php artisan make:migration:schema remove_excerpt_from_posts_table --schema="excerpt:string:nullable"`


Now, when you create a migration, you typically want a model to go with it, right? By default, we'll go ahead and create an Eloquent model to go with your migration.
This means, if you run, say:

```
php artisan make:migration:schema create_dogs_table --schema="name:string"
```

You'll get a migration, populated with the schema... and if you pass ```--model=true``` you'll also get an Eloquent model at `app/Dog.php`.

If you wish to specify a different path for your migration file, you can use the `--path` option like so:
```
php artisan make:migration:schema create_dogs_table --path=\database\migrations\pets
```

#### Foreign Constraints

There's also a secret bit of sugar for when you need to generate foreign constraints. Imagine that you have a posts table, where each post belongs to a user. Let's try:

```
php artisan make:migration:schema create_posts_table --schema="user_id:unsignedInteger:foreign, title:string, body:text"
```

Notice that "foreign" option (`user_id:unsignedInteger:foreign`)? That's special. It signals that `user_id` should receive a foreign constraint. Following conventions, this will give us:

```
$table->unsignedInteger('user_id');
$table->foreign('user_id')->references('id')->on('users');
```

As such, for that full command, our schema should look like so:

```
Schema::create('posts', function(Blueprint $table) {
	$table->increments('id');
	$table->unsignedInteger('user_id');
	$table->foreign('user_id')->references('id')->on('users');
	$table->string('title');
	$table->text('body');
	$table->timestamps();
);
```

Neato.

### Pivot Tables

So you need a migration to setup a pivot table in your database? Easy. We can scaffold the whole class with a single command.

```
php artisan make:migration:pivot tags posts
```

Here we pass, in any order, the names of the two tables that we need a joining/pivot table for. This will give you:

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostTagPivotTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('post_tag', function(Blueprint $table) {
			$table->integer('post_id')->unsigned()->index();
			$table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
			$table->integer('tag_id')->unsigned()->index();
			$table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('post_tag');
	}

}
```

> Notice that the naming conventions are being followed here, regardless of what order you pass the table names.
