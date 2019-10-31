<?php

namespace Gettext\Robo;

/**
 * Trait to scan files to get gettext entries.
 */
trait Gettext
{
    /**
     * Init a gettext task.
     */
    protected function taskGettext()
    {
        return $this->task(Scanner::class);
    }
}
