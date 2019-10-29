<?php

namespace Gettext\Robo;

use Gettext\Translations;
use Gettext\Scanner\ScannerInterface;
use Gettext\Scanner\PhpScanner;
use Gettext\Loader\PoLoader;
use Gettext\Generator\PoGenerator;
use Gettext\Merge;
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
trait Gettext
{
    /**
     * Init a gettext task.
     */
    protected function Gettext(string ...$domains)
    {
        return $this->task(Scanner::class, $domains);
    }
}

class Scanner extends BaseTask implements TaskInterface
{
    private $iterator;
    private $domains = [];
    private $defaultDomain;

    public function __construct(array $domains)
    {
        $this->iterator = new MultipleIterator(MultipleIterator::MIT_NEED_ANY);
    }

    public function defaultDomain(string $domain): self
    {
        $this->defaultDomain = $domain;
    }

    /**
     * Scan php files
     */
    public function scan(string $path, string $regex = null): self
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
     * Add a new domain
     */
    public function domain(?string $domain, string ...$targets): self
    {
        $this->domains[$domain] = [
            'translations' => Translations::create($domain),
            'targets' => $targets
        ];

        return $this;
    }

    /**
     * Run the task.
     */
    public function run()
    {
        $scanner = new PhpScanner(...array_column($this->domains, 'translations'));

        if ($this->defaultDomain) {
            $scanner->setDefaultDomain($this->defaultDomain);
        }

        $this->runScanner($scanner);

        $allTranslations = $scanner->getTranslations();

        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();

        foreach ($allTranslations as $domain => $translations) {
            $targets = $this->domains[$domain]['targets'];

            foreach ($targets as $target) {
                if (is_file($target)) {
                    $targetTranslations = $poLoader->loadFile($target);
                    $merged = $translations->mergeWith($targetTranslations, Merge::SCAN_AND_LOAD);
                } else {
                    $dir = dirname($target);

                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }

                    $merged = $translations;
                }

                $poGenerator->generateFile($merged, $target);

                $this->printTaskInfo("Gettext exported to {$target}");
            }
        }

        return Result::success($this, 'All gettext generated successfuly!');
    }

    /**
     * Execute the scanner.
     */
    private function runScanner(ScannerInterface $scanner)
    {
        foreach ($this->iterator as $each) {
            foreach ($each as $file) {
                if ($file === null || !$file->isFile()) {
                    continue;
                }

                $scanner->scanFile($file->getPathname());
            }
        }
    }
}
