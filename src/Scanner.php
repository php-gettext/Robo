<?php

namespace Gettext\Robo;

use FilesystemIterator;
use Gettext\Generator\ArrayGenerator;
use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\ArrayLoader;
use Gettext\Loader\MoLoader;
use Gettext\Loader\PoLoader;
use Gettext\Merge;
use Gettext\Scanner\PhpScanner;
use Gettext\Scanner\ScannerInterface;
use Gettext\Translations;
use MultipleIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use RuntimeException;

class Scanner extends BaseTask implements TaskInterface
{
    private $iterator;
    private $domains = [];

    private static $formats = [
        '.po' => [
            PoLoader::class,
            PoGenerator::class,
        ],
        '.mo' => [
            MoLoader::class,
            MoGenerator::class,
        ],
        '.php' => [
            ArrayLoader::class,
            ArrayGenerator::class,
        ],
    ];

    public function __construct()
    {
        $this->iterator = new MultipleIterator(MultipleIterator::MIT_NEED_ANY);
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
     * Save the translations
     */
    public function save(string $domain, string ...$targets): self
    {
        if (!isset($this->domains[$domain])) {
            $this->domains[$domain] = [
                'translations' => Translations::create($domain),
                'targets' => [],
            ];
        }

        $this->domains[$domain]['targets'][] = $targets;

        return $this;
    }

    /**
     * Run the task.
     */
    public function run()
    {
        $scanner = new PhpScanner(...array_column($this->domains, 'translations'));
        $scanner->setDefaultDomain(key($this->domains));

        $this->runScanner($scanner);

        $allTranslations = $scanner->getTranslations();

        foreach ($allTranslations as $domain => $translations) {
            foreach ($this->domains[$domain]['targets'] as $targets) {
                $mainTarget = $targets[0];

                if (is_file($mainTarget)) {
                    $loader = self::getLoader($mainTarget);

                    $targetTranslations = (new $loader())->loadFile($mainTarget);
                    $translations = $translations->mergeWith($targetTranslations, Merge::SCAN_AND_LOAD);
                }

                foreach ($targets as $target) {
                    $generator = self::getGenerator($target);
                    $dir = dirname($target);

                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }

                    (new $generator())->generateFile($translations, $target);

                    $this->printTaskInfo("Gettext exported to {$target}");
                }
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

    private static function getLoader(string $file): string
    {
        return self::getFormat($file)[0];
    }

    private static function getGenerator(string $file): string
    {
        return self::getFormat($file)[1];
    }

    private static function getFormat(string $file): array
    {
        $regex = '/('.str_replace('.', '\\.', implode('|', array_keys(self::$formats))).')$/';

        if (preg_match($regex, strtolower($file), $matches)) {
            return self::$formats[$matches[1]] ?? null;
        }

        throw new RuntimeException(
            sprintf('Invalid file "%s". No loader and generator compatible has been found', $file)
        );
    }
}
