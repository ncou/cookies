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
        // TODO : limiter les valeurs de "samesite" à "Lax" et "Strict". avec la valeur par défaut à Lax !!!! Eventuellement 'None' mais ce n'est pas recommandé
        return Expect::structure([
            'encrypt' => Expect::bool()->default(true),
            'excluded' => Expect::list(),
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
