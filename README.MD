## About

This package is based on the [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) package 
and adds PHPDoc generation functionality for models based on Laravel migrations (no database connection required). 
It uses the [phpstan/phpstan](https://github.com/phpstan/phpstan) package, 
and specifically [larastan/larastan](https://github.com/larastan/larastan), 
to generate model property types from migration files.

## Installation

You can install the package via composer:

```bash
composer require maksmartyn/laravel-ide-helper
```

## Usage

To generate the PHPDocs for your models, you can run the following command:

```bash
php artisan ide-helper:models-from-migrations
```

This console command is completely analogous to the original `ide-helper:models` command in terms of its operating logic, 
accepted parameters and flags, and also uses the configuration of the original package, but does not use a connection to the database.