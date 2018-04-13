![](https://raw.githubusercontent.com/tightenco/quicksand/master/quicksand-logo.png)

# Quicksand

Schedule a force delete of your soft deleted Eloquent models after they've been soft deleted for a given period of time.

Quicksand is an Artisan command that you can run in your scheduler daily.

### Using an older version of PHP?

If you're using PHP 7.0 or lower, please use version 0.2 of this package.

## Installation

1. Add Quicksand to your Composer file: `composer require tightenco/quicksand`
2. Register the Quicksand Service provider in `config/app.php`:
    
    ```php
        'providers' => [
            ...

            Tightenco\Quicksand\QuicksandServiceProvider::class,
    ```
3. Publish your config: `php artisan vendor:publish --provider="Tightenco\Quicksand\QuicksandServiceProvider"`
4. Edit your config. Define which classes you'd like to have Quicksand clean up for you, and how many days Quicksand should wait to clean up.
5. Schedule the command in `app/Console/Kernel.php`:

    ```php
        protected function schedule(Schedule $schedule)
        {
            $schedule->command('quicksand:run')
                ->daily();
        }
    ```
    
## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/tightenco/quicksand/tags). 

## Authors

* **Benson Lee** - [besologic](https://github.com/besologic)
* **Matt Stauffer** - [mattstauffer](https://github.com/mattstauffer)

See also the list of [contributors](https://github.com/tightenco/quicksand/graphs/contributors) who participated in this project.

This package is developed and maintained by [Tighten](https://tighten.co).

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE) file for details
