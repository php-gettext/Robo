# GettextRobo

[Robo](http://robo.li) task to extract gettext values from files using [gettext/gettext](https://github.com/oscarotero/Gettext) library

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

class RoboFile extends \Robo\Tasks
{
    use Gettext\Robo\GettextScanner;

    /**
     * Scan files to find new gettext values
     */
    public function gettext()
    {
        $this->taskGettextScanner()
            ->extract('templates/')
            ->extract('js/', '/.*\.js/') //directory + regex
            ->generate('Locale/gl/LC_MESSAGES/messages.mo')
            ->generate('Locale/es/LC_MESSAGES/messages.mo')
            ->generate('Locale/en/LC_MESSAGES/messages.mo')
            ->run();
    }
}
```

Use robo to run the code:

```bash
robo gettext
```