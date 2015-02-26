# Laravel 5 Extended Generators

[![Build Status](https://travis-ci.org/laracasts/Laravel-5-Generators-Extended.svg?branch=master)](https://travis-ci.org/laracasts/Laravel-5-Generators-Extended)

If you're familiar with my [Laravel 4 Generators](https://github.com/JeffreyWay/Laravel-4-Generators), then this is basically the same thing - just upgraded for Laravel 5.

L5 includes a bunch of generators out of the box, so this package only needs to add a few things, like:

- `make:migration:schema`
- `make:migration:pivot`
- `make:seed`

*With one or two more to come.*

## Usage

### Step 1: Install Through Composer

```
composer require 'laracasts/generators' --dev
```

### Step 2: Add the Service Provider

Open `config/app.php` and, to your "providers" array at the bottom, add:

```
"Laracasts\Generators\GeneratorsServiceProvider"
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
- `php artisan make:migration:schema create_posts_table --schema="title:string, body:text, except:string:nullable"`
- `php artisan make:migration:schema remove_excerpt_from_posts_table --schema="except:string:nullable"`


Now, when you create a migration, you typically want a model to go with it, right? By default, we'll go ahead and create an Eloquent model to go with your migration.
This means, if you run, say:

```
php artisan make:migration:schema create_dogs_table --schema="name:string"
```

You'll get a migration, populated with the schema...but you'll also get an Eloquent model at `app/Dog.php`. Naturally, you can opt out of this by adding the `--model=false` flag/option.

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
