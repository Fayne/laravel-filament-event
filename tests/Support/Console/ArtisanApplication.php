<?php

namespace Tests\Support\Console;

use Illuminate\Console\Application as BaseApplication;
use Illuminate\Console\Command as LaravelCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ArtisanApplication extends BaseApplication
{
    public function addCommand(callable|SymfonyCommand $command): ?SymfonyCommand
    {
        if ($command instanceof LaravelCommand) {
            $command->setLaravel($this->laravel);
        }

        return parent::addCommand($command);
    }
}
