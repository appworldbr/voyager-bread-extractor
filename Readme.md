# Voyager BREAD Extractor

Extract Voyager BREAD from database to Seeder File.

## Getting Started

### Prerequisites

- Laravel
- Voyager

### Installing

```dash
composer require appworldbr/voyager-bread-extractor
```

## Usage

After create the BREAD manually, run `php artisan voyager:extract ModelWithNamespace`

ex.

```dash

php artisan voyager:extract App/Models/Products/Product

```

Them a file in `database/seeds` will be created with `data_types`, `data_row`, `menu_item`, `permissions` and `BREAD translation` seeds.

After that, if you want to migrate with this seed, first you will need run `composer dump-autoload` ([read more](https://laravel.com/docs/7.x/seeding#running-seeders)) and them run `php artisan db:seed --class=CLASSNAME` ([read more](https://laravel.com/docs/7.x/seeding#running-seeders)).

```dash
php artisan db:seed --class=ProductsBREADTableSeeder
```

or you can add the Seeder file in `DatabaseSeeder.php` file ([read more](https://laravel.com/docs/7.x/seeding#introduction))

and them run `php artisan db:seed` ([read more](https://laravel.com/docs/7.x/seeding#introduction))

## Authors

* **Marcelo Araujo Jr** - *Initial work* - [AppWorld](https://appworld.com.br/)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
