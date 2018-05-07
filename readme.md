# Laravel 5 Extended Generators

[![Build Status](https://travis-ci.org/laracasts/Laravel-5-Generators-Extended.svg?branch=master)](https://travis-ci.org/laracasts/Laravel-5-Generators-Extended)

If you're familiar with my [Laravel 4 Generators](https://github.com/JeffreyWay/Laravel-4-Generators), then this is basically the same thing - just upgraded for Laravel 5.

L5 includes a bunch of generators out of the box, so this package only needs to add a few things, like:

- `make:migration:schema`
- `make:migration:pivot`
- `make:seed`

*With one or two more to come.*


## Usage on Laravel 5.5

### Step 1: Install Through Composer

```
composer require laracasts/generators --dev
```

### Step 2: Run Artisan!

You're all set. Run `php artisan` from the console, and you'll see the new commands in the `make:*` namespace section.


## Usage on Laravel 5.4 and 5.3

### Step 1: Install Through Composer

```
composer require laracasts/generators --dev
```

### Step 2: Add the Service Provider

You'll only want to use these generators for local development, so you don't want to update the production  `providers` array in `config/app.php`. Instead, add the provider in `app/Providers/AppServiceProvider.php`, like so:

```php
public function register()
{
	if ($this->app->environment() == 'local') {
		$this->app->register('Laracasts\Generators\GeneratorsServiceProvider');
	}
}
```


### Step 3: Run Artisan!

You're all set. Run `php artisan` from the console, and you'll see the new commands in the `make:*` namespace section.

## Examples

- [Migrations With Schema](#migrations-with-schema)
- [Pivot Tables](#pivot-tables)
- [Database Seeders](#database-seeders)

### Migrations With Schema

```
php artisan make:migration:schema create_users_table --schema="username:string, email:string:unique"
```

Notice the format that we use, when declaring any applicable schema: a comma-separate list...

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

You'll get a migration, populated with the schema...but you'll also get an Eloquent model at `app/Dog.php`. Naturally, you can opt out of this by adding the `--model=0` flag/option.

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
		Schema::create('post_tag', function(Blueprint $table)
		{
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

### Database Seeders

```
php artisan make:seed posts
```

This one is fairly basic. It just gives you a quick seeder class in the "database/seeds" folder.

```php
<?php

use Illuminate\Database\Seeder;

// composer require laracasts/testdummy
use Laracasts\TestDummy\Factory as TestDummy;

class PostsTableSeeder extends Seeder {

	public function run()
	{
        // TestDummy::times(20)->create('App\Post');
	}

}
```
