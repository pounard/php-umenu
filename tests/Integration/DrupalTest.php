<?php

namespace MakinaCorpus\Umenu\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SymfonyTest extends KernelTestCase
{
    public function testGoatIntegration()
    {
        // @todo
        $this->markTestSkipped("there is a dependency problem that needs fixing");

        self::bootKernel();
        $container = self::$kernel->getContainer();

        //
    }

    protected static function getKernelClass()
    {
        return DrupalKernel::class;
    }
}
