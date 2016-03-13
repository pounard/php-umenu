<?php

namespace MakinaCorpus\Umenu\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Umenu\DrupalMenuStorage;

class DrupalMenuStorageTest extends AbstractDrupalTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->getDatabaseConnection()->query("DELETE FROM {umenu}");
    }

    public function testCrud()
    {
        $storage = new DrupalMenuStorage($this->getDatabaseConnection());

        // Load a non existing menu (exception)
        try {
            $storage->load('foo');
            $this->fail();
        } catch (\InvalidArgumentException $e) {}

        // Load many names (no exceptions, but empty)
        $ret = $storage->loadMultiple(['foo', 'bar', 'baz']);
        $this->assertCount(0, $ret);

        // Create a new menu
        $created = $storage->create('foo', ['title' => "Foo!"]);
        $this->assertTrue(is_array($created));
        $this->assertSame('foo', $created['name']);
        $this->assertSame('Foo!', $created['title']);
        $this->assertEmpty($created['description']);

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
        $this->assertSame('foo', $foo['name']);
        $this->assertSame('Booh', $foo['title']);
        $bar = $storage->load('bar');
        $this->assertSame('bar', $bar['name']);
        $this->assertSame('The Bar', $bar['title']);

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
        $this->assertSame('a', $ret['a']['name']);
        $this->assertSame('b', $ret['b']['name']);

        $ret = $storage->loadWithConditions([
            'title'       => ['tarte', 'lapin'],
            'description' => ['cassoulet', 'roger'],
        ]);
        $this->assertCount(4, $ret);
        ksort($ret);
        $this->assertSame('a', $ret['a']['name']);
        $this->assertSame('b', $ret['b']['name']);
        $this->assertSame('c', $ret['c']['name']);
        $this->assertSame('d', $ret['d']['name']);
    }

    public function testEvents()
    {
        // @todo
    }
}
