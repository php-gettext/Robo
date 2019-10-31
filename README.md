# Robo Tasks

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
            ->scan('templates/')
            ->scan('other-files/', '/.*\.php/')

            //Save the result in different domains
            ->domain(
                'domain1',
                'Locale/gl/LC_MESSAGES/domain1.mo',
                'Locale/es/LC_MESSAGES/domain1.mo'
            )
            ->domain(
                'domain2',
                'Locale/gl/LC_MESSAGES/domain2.mo',
                'Locale/es/LC_MESSAGES/domain2.mo'
            )
            ->run();
    }
}
```

Use robo to run the code:

```bash
robo gettext
```