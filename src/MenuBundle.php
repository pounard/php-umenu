<?php

namespace MakinaCorpus\Umenu;

use MakinaCorpus\Umenu\DependencyInjection\MenuExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MenuBundle extends Bundle
{
    protected function createContainerExtension()
    {
        return new MenuExtension();
    }

    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = $this->createContainerExtension();
        }

        return $this->extension;
    }
}
