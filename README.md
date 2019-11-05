# Robo Tasks

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]

[Robo](http://robo.li) task to extract gettext values from files using [gettext/gettext](https://github.com/php-gettext/Gettext) library

Created by Oscar Otero <http://oscarotero.com> <oom@oscarotero.com> (MIT License)

## Install

Using composer:

```bash
composer require gettext/robo
```

## usage example

Create a RoboFile.php with the following code:

```php
require 'vendor/autoload.php';

use Robo\Tasks;

class RoboFile extends Robo\Tasks
{
    use Gettext\Robo\Gettext;

    /**
     * Scan files to find new gettext values
     */
    public function gettext()
    {
        $this->taskGettext()
            //Scan folders with php files
            ->scan('templates/', '/\.php$/')
            ->scan('other-files/', '/\.php$/')

            //Save the domain1
            ->save('domain1', 'Locale/es/LC_MESSAGES/domain1.po')
            ->save('domain1', 'Locale/gl/LC_MESSAGES/domain1.po')

            //Save the domain2
            ->save('domain2', 'Locale/es/LC_MESSAGES/domain1.po')
            ->save('domain2', 'Locale/gl/LC_MESSAGES/domain1.po')

            ->run();
    }
}
```

Use robo to run the code:

```bash
robo gettext
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/gettext/robo.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/php-gettext/Robo.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/gettext/robo.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/gettext/robo
[link-scrutinizer]: https://scrutinizer-ci.com/g/php-gettext/Robo
[link-downloads]: https://packagist.org/packages/gettext/robo
