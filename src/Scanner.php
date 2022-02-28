<?php

namespace Gettext\Robo;

use FilesystemIterator;
use Gettext\Generator\ArrayGenerator;
use Gettext\Generator\GeneratorInterface;
use Gettext\Generator\JsonGenerator;
use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\ArrayLoader;
use Gettext\Loader\JsonLoader;
use Gettext\Loader\LoaderInterface;
use Gettext\Loader\MoLoader;
use Gettext\Loader\PoLoader;
use Gettext\Merge;
use Gettext\Scanner\JsScanner;
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
    private $scanners = [
        '.php' => PhpScanner::class,
        '.phtml' => PhpScanner::class,
        '.js' => JsScanner::class,
    ];

    private $loaders = [
        '.po' => PoLoader::class,
        '.mo' => MoLoader::class,
        '.php' => ArrayLoader::class,
        '.phtml' => ArrayLoader::class,
        '.json' => JsonLoader::class,
    ];

    private $generators = [
        '.po' => PoGenerator::class,
        '.mo' => MoGenerator::class,
        '.php' => ArrayGenerator::class,
        '.phtml' => ArrayGenerator::class,
        '.json' => JsonGenerator::class,
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
        $allTranslations = $this->runScanner();

        foreach ($allTranslations as $domain => $translations) {
            foreach ($this->domains[$domain]['targets'] as $targets) {
                $mainTarget = $targets[0];

                if (is_file($mainTarget)) {
                    $targetTranslations = $this->getLoaderFor($mainTarget)->loadFile($mainTarget);
                    $translations = $translations->mergeWith($targetTranslations, Merge::SCAN_AND_LOAD);
                }

                foreach ($targets as $target) {
                    $dir = dirname($target);

                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }

                    $this->getGeneratorFor($target)->generateFile($translations, $target);
                    $this->printTaskInfo("Gettext exported to {$target}");
                }
            }
        }

        return Result::success($this, 'All gettext generated successfuly!');
    }

    /**
     * Execute the scanner.
     *
     * @return Translations[]
     */
    private function runScanner(): array
    {
        foreach ($this->iterator as $each) {
            foreach ($each as $file) {
                if ($file === null || !$file->isFile()) {
                    continue;
                }

                $this->getScannerFor($file)->scanFile($file->getPathname());
            }
        }

        $allTranslations = [];

        foreach ($this->scanners as $scanner) {
            if (!($scanner instanceof ScannerInterface)) {
                continue;
            }

            foreach ($scanner->getTranslations() as $domain => $translations) {
                if (!isset($allTranslations[$domain])) {
                    $allTranslations[$domain] = $translations;
                    continue;
                }

                $allTranslations[$domain] = $allTranslations[$domain]->mergeWith($translations);
            }
        }

        return $allTranslations;
    }

    private function getScannerFor(string $file): ScannerInterface
    {
        $format = self::getFormat(array_keys($this->scanners), $file);

        if (!$format) {
            throw new RuntimeException(sprintf('Invalid file "%s". No scanner has been found for this format', $file));
        }

        if (is_string($this->scanners[$format])) {
            $class = $this->scanners[$format];
            $scanner = new $class(...array_column($this->domains, 'translations'));
            $scanner->setDefaultDomain(key($this->domains));

            return $this->scanners[$format] = $scanner;
        }

        return $this->scanners[$format];
    }

    private function getLoaderFor(string $file): LoaderInterface
    {
        $format = self::getFormat(array_keys($this->loaders), $file);

        if (!$format) {
            throw new RuntimeException(sprintf('Invalid file "%s". No loader has been found for this format', $file));
        }

        if (is_string($this->loaders[$format])) {
            $class = $this->loaders[$format];
            return $this->loaders[$format] = new $class();
        }

        return $this->loaders[$format];
    }

    private function getGeneratorFor(string $file): GeneratorInterface
    {
        $format = self::getFormat(array_keys($this->generators), $file);

        if (!$format) {
            throw new RuntimeException(sprintf('Invalid file "%s". No generator has been found for this format', $file));
        }

        if (is_string($this->generators[$format])) {
            $class = $this->generators[$format];
            return $this->generators[$format] = new $class();
        }

        return $this->generators[$format];
    }

    private static function getFormat(array $formats, string $file): ?string
    {
        $regex = '/('.str_replace('.', '\\.', implode('|', $formats)).')$/';

        return preg_match($regex, strtolower($file), $matches) ? $matches[1] : null;
    }
}
