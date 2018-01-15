<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal;

use MakinaCorpus\Umenu\Menu;
use PHPUnit\Framework\TestCase;

abstract class AbstractMenuStorageTest extends TestCase
{
    use MenuTestTrait;

    protected function setUp()
    {
        parent::setUp();

        $storage = $this->getMenuStorage();
        $storage->delete('foo');
        $storage->delete('bar');
        $storage->delete('baz');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $storage = $this->getMenuStorage();
        $storage->delete('foo');
        $storage->delete('bar');
        $storage->delete('baz');
    }

    public function testCrud()
    {
        $storage = $this->getMenuStorage();

        // Load a non existing menu (exception)
        try {
            $storage->load('foo');
            $this->fail("foo should not have been loaded");
        } catch (\InvalidArgumentException $e) {}

        // Load many names (no exceptions, but empty)
        $ret = $storage->loadMultiple(['foo', 'bar', 'baz']);
        $this->assertCount(0, $ret);

        // Create a new menu
        $created = $storage->create('foo', ['title' => "Foo!"]);
        $this->assertTrue($created instanceof Menu);
        $this->assertSame('foo', $created->getName());
        $this->assertSame('Foo!', $created->getTitle());
        $this->assertEmpty($created->getDescription());

        // Attempt to recreate it (exception)
        try {
            $storage->create('foo');
            $this->fail();
        } catch (\InvalidArgumentException $e) {}

        // Create another for fun
        $storage->create('bar', ['title' => 'The Bar']);

        // Update the menu, attempt overriding the name
        $storage->update('foo', ['name' => 'arg', 'title' => "Booh"]);

        // Ensure 'arg' does not exist
        try {
            $storage->load('arg');
            $this->fail();
        } catch (\InvalidArgumentException $e) {}

        // Load it back, all of them, check one has been updated not the others
        $foo = $storage->load('foo');
        $this->assertSame('foo', $foo->getName());
        $this->assertSame('Booh', $foo->getTitle());
        $bar = $storage->load('bar');
        $this->assertSame('bar', $bar->getName());
        $this->assertSame('The Bar', $bar->getTitle());

        // Delete 'foo' ensures that 'bar' still exists
        $storage->delete('foo');
        try {
            $storage->load('foo');
            $this->fail();
        } catch (\InvalidArgumentException $e) {}
        $storage->load('bar');

        // Create many menus
        $storage->create('a', ['title' => 'tarte',    'description' => 'cassoulet']);
        $storage->create('b', ['title' => 'tarte',    'description' => 'roger']);
        $storage->create('c', ['title' => 'lapin',    'description' => 'cassoulet']);
        $storage->create('d', ['title' => 'lapin',    'description' => 'roger']);
        $storage->create('e', ['title' => 'some',     'description' => 'other']);
        $storage->create('f', ['title' => 'useless',  'description' => 'menu']);

        // Load a few of them using conditions
        $ret = $storage->loadWithConditions([
            'title'       => 'tarte',
            'description' => 'cassoulet',
        ]);
        $this->assertCount(1, $ret);

        $ret = $storage->loadWithConditions([
            'title'       => 'tarte',
        ]);
        $this->assertCount(2, $ret);
        ksort($ret);
        $this->assertSame('a', $ret['a']->getName());
        $this->assertSame('b', $ret['b']->getName());

        $ret = $storage->loadWithConditions([
            'title'       => ['tarte', 'lapin'],
            'description' => ['cassoulet', 'roger'],
        ]);
        $this->assertCount(4, $ret);
        ksort($ret);
        $this->assertSame('a', $ret['a']->getName());
        $this->assertSame('b', $ret['b']->getName());
        $this->assertSame('c', $ret['c']->getName());
        $this->assertSame('d', $ret['d']->getName());
    }


//     public function testMainMenuStatus()
//     {
//         // @todo We have a dependency problem with ucms_site
//     }

//     public function testEvents()
//     {
//         // @todo
//     }
}
