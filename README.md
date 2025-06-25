# This is my package panel-translate

[![Latest Version on Packagist](https://img.shields.io/packagist/v/smartcms/panel-translate.svg?style=flat-square)](https://packagist.org/packages/smartcms/panel-translate)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/smartcms/panel-translate/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/smartcms/panel-translate/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/smartcms/panel-translate/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/smartcms/panel-translate/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/smartcms/panel-translate.svg?style=flat-square)](https://packagist.org/packages/smartcms/panel-translate)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require smart-cms/panel-translate
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="panel-translate-views"
```


## Usage

```php
\SmartCms\PanelTranslate\PanelTranslatePlugin::make()
```
Optionally you can pass a navigation group for translate page

```php
\SmartCms\PanelTranslate\PanelTranslatePlugin::make(__('system'))
```


## Testing

```bash
composer test
```

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [maxboyko](https://github.com/SmartCms)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
