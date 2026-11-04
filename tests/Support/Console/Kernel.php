<?php

namespace Tests\Support\Console;

use Orchestra\Testbench\Foundation\Console\Kernel as BaseKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Kernel extends BaseKernel
{
    protected function getArtisan()
    {
        if (is_null($this->artisan)) {
            $this->artisan = (new ArtisanApplication($this->app, $this->events, $this->app->version()))
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();

            if ($this->symfonyDispatcher instanceof EventDispatcher) {
                $this->artisan->setDispatcher($this->symfonyDispatcher);
                $this->artisan->setSignalsToDispatchEvent();
            }
        }

        return $this->artisan;
    }
}
