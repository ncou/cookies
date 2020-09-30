<?php

declare(strict_types=1);

namespace Chiron\Cookies\Config;

use Chiron\Config\AbstractInjectableConfig;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class CookiesConfig extends AbstractInjectableConfig
{
    protected const CONFIG_SECTION_NAME = 'cookies';

    protected function getConfigSchema(): Schema
    {
        return Expect::structure([
            'encrypt' => Expect::bool()->default(true),
            'excluded' => Expect::list()->default(['PHPSESSID', 'csrf-token']),
        ]);
    }

    public function getEncrypt(): bool
    {
        return $this->get('encrypt');
    }

    public function getExcluded(): array
    {
        return $this->get('excluded');
    }
}
