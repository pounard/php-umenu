<?php

namespace MakinaCorpus\Umenu\Tests\Integration;

use MakinaCorpus\Umenu\TreeManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DependencyInjectionTest extends KernelTestCase
{
    public function testGoatIntegration()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        $manager = $container->get('umenu.manager');
        $this->assertInstanceOf(TreeManager::class, $manager);
    }

    /**
     * {@inheritdoc}
     */
    protected static function getKernelClass()
    {
        return SymfonyKernel::class;
    }
}
