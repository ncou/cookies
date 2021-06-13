<?php

declare(strict_types=1);

namespace Chiron\Cookies\Bootloader;

use Chiron\Core\Directories;
use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Publisher\Publisher;

final class PublishCookiesBootloader extends AbstractBootloader
{
    public function boot(Publisher $publisher, Directories $directories): void
    {
        // copy the configuration file template from the package "config" folder to the user "config" folder.
        $publisher->add(__DIR__ . '/../../config/cookies.php.dist', $directories->get('@config/cookies.php'));
    }
}
