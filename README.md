![Quicksand logo](https://raw.githubusercontent.com/tighten/quicksand/main/quicksand-banner.png)

[ ![Codeship Status for tightenco/quicksand](https://app.codeship.com/projects/a9e67790-46e9-0134-a8cb-3a2b7d7aa9d2/status?branch=master)](https://app.codeship.com/projects/169050)

# Quicksand

Schedule a force delete of your soft deleted Eloquent models or pivot tables after they've been soft deleted for a given period of time.

Quicksand is an Artisan command that you can run in your scheduler daily.

## Requirements

- If you are using Laravel 5.6 or higher, use version `2.0` of this package.
- If you are using Laravel 5.5 and running PHP 7.1 or higher, use version `1.0` of this package.
- If you are using Laravel 5.4 or lower, or PHP 7.0 or lower, please use version `0.2` of this package.

## Installation

1. Add Quicksand to your Composer file: `composer require tightenco/quicksand`
2. Register the Quicksand Service provider in `config/app.php` (you can skip this step if you're using Laravel 5.5 or higher due to package auto-discovery):
    
    ```php
    'providers' => [
        ...

        Tightenco\Quicksand\QuicksandServiceProvider::class,
    ```
3. Publish your config: `php artisan vendor:publish --provider="Tightenco\Quicksand\QuicksandServiceProvider"`
4. Edit your config. Define which classes and/or pivot tables you'd like to have Quicksand clean up for you, how many days Quicksand should wait to clean up, and whether or not the results should be logged. The default `'days'` until cleaning up is overridable by specifying a `'days'` key when registering a model or pivot table:

    1. _Note: Quicksand will disregard any global scopes applied to models when deleting._
    2. _Note: Prior to version 2.3 the `deletables` configuration key was named `models` and did not support pivot tables._

    ```php
    'days' => 30,

    'deletables' => [
        App\Default::class,
        App\CleanEveryTwentyDays::class => [
            'days'  => 20 // override default 'days'
        ],
        'example_pivot',
        'example_pivot' => [
            'days'  => 20 // override default 'days'
        ]
    ]
    ```

5. Schedule the command in `app/Console/Kernel.php`:

    ```php
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('quicksand:run')
            ->daily();
    }
    ```
### Using a Custom Log File

If you are using Laravel 5.6 or higher, you can customize the logger Quicksand uses by adding a `quicksand` channel to your `logging.php` config file like so:

```php
'channels' => [
    /* ... */
    'quicksand' => [
        'driver' => 'single',
        'path' => storage_path('logs/quicksand.log'),
        'level' => 'info',
    ],
]
```

If you are using Laravel 5.5 or lower, you can customize the logger Quicksand uses by editing the `custom_log_file` option in your `quicksand.php` config file.

By default, Quicksand will log to the standard `laravel.log` file.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/tighten/quicksand/tags). 

## Authors

* **Benson Lee** - [besologic](https://github.com/besologic)
* **Matt Stauffer** - [mattstauffer](https://github.com/mattstauffer)

See also the list of [contributors](https://github.com/tighten/quicksand/graphs/contributors) who participated in this project.

This package is developed and maintained by [Tighten](https://tighten.co).

## Testing
You can test this package by running
```
composer test
```

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE) file for details
