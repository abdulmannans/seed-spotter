# SeedSpotter

SeedSpotter is a Laravel package that helps you detect discrepancies between your database seeders and the actual database content. It's an essential tool for maintaining data integrity in your Laravel applications.

## Features

- Compare seeder data with actual database content
- Detect missing, extra, or different rows
- Ignore specified columns in comparisons
- Easy-to-use Artisan command
- Configurable through Laravel's config system

## Installation

You can install the package via composer:

```bash
composer require abdulmannans/seed-spotter
```


The package will automatically register its service provider.

## Usage

### Using the Artisan Command

The easiest way to use SeedSpotter is through its Artisan command:

```bash
php artisan seed-spotter:compare "Database\Seeders\YourSeeder" --table=your_table_name
```

This command will run the comparison and display the results in your console.

### Using in Code

You can also use SeedSpotter in your PHP code:

```php
use Abdulmannans\SeedSpotter\Facades\SeedSpotter;

$result = SeedSpotter::compare(YourSeederClass::class, "your_table_name");

if ($result["has_discrepancies"]) {
  foreach ($result["discrepancies"] as $discrepancy) {
    // Handle discrepancy
  }
} else {
  // Data is in sync
}
```

## Configuration

To customize SeedSpotter's behavior, you can publish the configuration file:

```bash
php artisan vendor:publish --provider="Abdulmannans\SeedSpotter\SeedSpotterServiceProvider" --tag="config"
```

This will create a `config/seed-spotter.php` file. Here are the available options:

```php
return [
  // The default table to check if not specified
  "table" => "users",

  // Columns to ignore in comparisons
  "ignore_columns" => ["created_at", "updated_at"],
];
```

## Testing

To run the package tests, use:

```bash
composer test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- Abdul Mannan (https://github.com/abdulmannans)
- [All Contributors](../../contributors)

## Support

If you discover any security related issues, please email samannan1999@gmail.com instead of using the issue tracker.

---

Made with ❤️ by Abdul Mannan(https://github.com/abdulmannans)
