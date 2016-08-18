![](https://raw.githubusercontent.com/tightenco/quicksand/master/quicksand-logo.png)

# Quicksand

Schedule a force delete of your soft deleted Eloquent models after they've been soft deleted for a given period of time.

Quicksand is an Artisan command that you can run in your scheduler daily.

## Installation instructions

1. Add Quicksand to your Composer file: `composer require tightenco/quicksand`
2. Register the Quicksand Service provider in `config/app.php`:
    
    ```php
        'providers' => [
            ...

            Tightenco\Quicksand\QuicksandServiceProvider::class,
    ```
3. Publish your config: `php artisan vendor:publish --provider="Tightenco\Quicksand\QuicksandServiceProvider"`
4. Edit your config. Define which classes you'd like to have Quicksand clean up for you, and how many days Quicksand should wait to clean up.
5. Schedule the command in `app/Http/Console/Kernel.php`:

    ```php
        protected function schedule(Schedule $schedule)
        {
            $schedule->command('quicksand:run')
                ->daily();
        }
    ```
