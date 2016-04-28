<?php
namespace Gettext\Robo;

use Gettext\Translations;
use FilesystemIterator;
use MultipleIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Robo\Task\BaseTask;
use Robo\Contract\TaskInterface;

/**
 * Trait to scan files to get gettext entries
 */
trait GettextScanner
{
    /**
     * Init a gettext task
     */
    protected function taskGettextScanner()
    {
        return new TaskGettextScanner();
    }
}

class TaskGettextScanner extends BaseTask implements TaskInterface
{
    protected $iterator;
    protected $targets = [];

    public function __construct()
    {
        $this->iterator = new MultipleIterator(MultipleIterator::MIT_NEED_ANY);
    }

    /**
     * Add a new source folder
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
            $iterator = new RegexIterator($iterator, $regex, RecursiveRegexIterator::GET_MATCH);
        }

        $this->iterator->attachIterator($iterator);

        return $this;
    }

    /**
     * Add a new target
     *
     * @param string $path
     *
     * @return $this
     */
    public function generate($path)
    {
        $this->targets[] = $path;

        return $this;
    }

    /**
     * Run the task
     */
    public function run()
    {
        foreach ($this->targets as $target) {
            $translations = new Translations();

            $this->scan($translations);

            if (is_file($target)) {
                $fn = $this->getFunctionName('from', $target, 'File');

                $translations->mergeWith(Translations::$fn($target), Translations::MERGE_HEADERS | Translations::MERGE_LANGUAGE | Translations::MERGE_PLURAL | Translations::MERGE_COMMENTS);
            }

            $fn = $this->getFunctionName('to', $target, 'File');
            $dir = dirname($target);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $translations->$fn($target);

            $this->printTaskInfo("Gettext exported to {$target}");
        }
    }

    /**
     * Execute the scan
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
     * Get the format based in the extension
     *
     * @param string $file
     *
     * @return string|null
     */
    private function getFunctionName($prefix, $file, $suffix)
    {
        switch (pathinfo($file, PATHINFO_EXTENSION)) {
            case 'php':
                return "{$prefix}PhpCode{$suffix}";

            case 'js':
                return "{$prefix}JsCode{$suffix}";

            case 'po':
            case 'pot':
                return "{$prefix}Po{$suffix}";

            case 'mo':
                return "{$prefix}Mo{$suffix}";
        }
    }
}
