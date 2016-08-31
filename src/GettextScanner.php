<?php

namespace Gettext\Robo;

use Gettext\Translations;
use FilesystemIterator;
use MultipleIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Robo\Task\BaseTask;
use Robo\Contract\TaskInterface;
use Robo\Result;

/**
 * Trait to scan files to get gettext entries.
 */
trait GettextScanner
{
    /**
     * Init a gettext task.
     */
    protected function taskGettextScanner()
    {
        return $this->task(TaskGettextScanner::class);
    }
}

class TaskGettextScanner extends BaseTask implements TaskInterface
{
    private $iterator;
    private $targets = [];

    private static $regex;
    private static $suffixes = [
        '.blade.php' => 'Blade',
        '.csv' => 'Csv',
        '.jed.json' => 'Jed',
        '.js' => 'JsCode',
        '.json' => 'Json',
        '.mo' => 'Mo',
        '.php' => ['PhpCode', 'PhpArray'],
        '.po' => 'Po',
        '.pot' => 'Po',
        '.twig' => 'Twig',
        '.xliff' => 'Xliff',
        '.yaml' => 'Yaml',
    ];

    public function __construct()
    {
        $this->iterator = new MultipleIterator(MultipleIterator::MIT_NEED_ANY);
    }

    /**
     * Add a new source folder.
     *
     * @param string      $path
     * @param null|string $regex
     *
     * @return $this
     */
    public function extract($path, $regex = null)
    {
        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);

        if ($regex) {
            $iterator = new RegexIterator($iterator, $regex);
        }

        $this->iterator->attachIterator($iterator);

        return $this;
    }

    /**
     * Add a new target.
     *
     * @param string $path
     *
     * @return $this
     */
    public function generate($path)
    {
        $this->targets[] = func_get_args();

        return $this;
    }

    /**
     * Run the task.
     */
    public function run()
    {
        foreach ($this->targets as $targets) {
            $target = $targets[0];
            $translations = new Translations();

            $this->scan($translations);

            if (is_file($target)) {
                $fn = $this->getFunctionName('from', $target, 'File', 1);
                $translations->mergeWith(Translations::$fn($target));
            }

            foreach ($targets as $target) {
                $fn = $this->getFunctionName('to', $target, 'File', 1);
                $dir = dirname($target);

                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                $translations->$fn($target);

                $this->printTaskInfo("Gettext exported to {$target}");
            }
        }

        return Result::success($this, 'All gettext generated successfuly!');
    }

    /**
     * Execute the scan.
     *
     * @param Translations $translations
     */
    private function scan(Translations $translations)
    {
        foreach ($this->iterator as $each) {
            foreach ($each as $file) {
                if ($file === null || !$file->isFile()) {
                    continue;
                }

                $target = $file->getPathname();

                if (($fn = $this->getFunctionName('addFrom', $target, 'File'))) {
                    $translations->$fn($target);
                }
            }
        }
    }

    /**
     * Get the format based in the extension.
     *
     * @param string $file
     *
     * @return string|null
     */
    private function getFunctionName($prefix, $file, $suffix, $key = 0)
    {
        if (preg_match(self::getRegex(), strtolower($file), $matches)) {
            $format = self::$suffixes[$matches[1]];

            if (is_array($format)) {
                $format = $format[$key];
            }

            return sprintf('%s%s%s', $prefix, $format, $suffix);
        }
    }

    /**
     * Returns the regular expression to detect the file format.
     * 
     * @param string
     */
    private static function getRegex()
    {
        if (self::$regex === null) {
            self::$regex = '/('.str_replace('.', '\\.', implode('|', array_keys(self::$suffixes))).')$/';
        }

        return self::$regex;
    }
}
