<?php

namespace Tests;

use KennedyOsaze\Container\Container;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Bar;
use Tests\Fixtures\Baz;
use Tests\Fixtures\Foo;
use Tests\Fixtures\FooBar;
use Tests\Fixtures\FooInterface;
use Tests\Fixtures\Plugh;
use Tests\Fixtures\Qux;
use Tests\Fixtures\Waldo;
use Tests\Fixtures\Xyzzy;

class ContainerTest extends TestCase
{
    public function testBinding()
    {
        $container = new Container;

        $container->bind('test', function () {
            return 'Test Value';
        });

        $container->bind('bar', Bar::class);

        $bindings = $container->getBindings();

        $this->assertNotEmpty($bindings);
        $this->assertCount(2, $bindings);
        $this->assertTrue($container->has('test'));
        $this->assertTrue($container->has('bar'));
    }

    public function testParameters()
    {
        $container = new Container;

        $container->bind('test', function ($container, $parameters) {
            return $parameters;
        });

        $result = $container->resolve('test', ['name' => 'kennedy']);

        $this->assertSame(['name' => 'kennedy'], $result);
    }

    public function testClassResolution()
    {
        $container = new Container;

        $container->bind('bar', Bar::class);

        $this->assertInstanceOf(Bar::class, $container->get('bar'));
    }

    public function testInterfaceResolution()
    {
        $container = new Container;

        $container->bind(FooInterface::class, Foo::class);

        $baz = $container->resolve(Baz::class);

        $this->assertInstanceOf(Baz::class, $baz);
        $this->assertInstanceOf(FooInterface::class, $baz->foo);
        $this->assertInstanceOf(Foo::class, $baz->foo);
    }

    public function testSingletonResolution()
    {
        $container = new Container;

        $container->singleton('bar', Bar::class);

        $object1 = $container->resolve('bar');
        $object2 = $container->resolve('bar');

        $this->assertSame($object1, $object2);
    }

    public function testInstance()
    {
        $container = new Container;
        $bar = new Bar;

        $container->instance('bar', $bar);

        $this->assertInstanceOf(Bar::class, $container->resolve('bar'));
        $this->assertSame($bar, $container->resolve('bar'));
    }

    public function testArrayAccess()
    {
        $container = new Container;

        $container['bar'] = function () {
            return new Bar;
        };

        $this->assertArrayHasKey('bar', $container->getBindings());
        $this->assertInstanceOf(Bar::class, $container['bar']);
        unset($container['bar']);
        $this->assertFalse(array_key_exists('bar', $container->getBindings()));
    }

    public function testMultipleDependentResolution()
    {
        $container = new Container;

        $container->bind(FooInterface::class, Foo::class);

        $foobar = $container->resolve(FooBar::class);

        $this->assertInstanceOf(FooInterface::class, $foobar->foo);
        $this->assertInstanceOf(Bar::class, $foobar->bar);
    }

    public function testPrimitiveDependant()
    {
        $container = new Container;

        $qux = $container->resolve(Qux::class, ['kennedy']);

        $this->assertSame($qux->name, 'kennedy');
    }

    public function testMultiplePrimitiveDependant()
    {
        $container = new Container;

        $object1 = $container->resolve(Plugh::class, ['kennedy', 'osaze', 20]);
        $object2 = $container->resolve(Plugh::class, ['number' => 20, 'first' => 'kennedy', 'last' => 'osaze']);


        $this->assertSame('kennedy', $object1->first);
        $this->assertSame('osaze', $object1->last);
        $this->assertSame(20, $object1->number);
        $this->assertSame(['first' => 'kennedy', 'last' => 'osaze', 'number' => 20], $object2->dump());
        $this->assertSame($object1->dump(), $object2->dump());
    }

    public function testMixedDependants()
    {
        $container = new Container;

        $waldo = $container->resolve(Waldo::class, ['first' => 'kennedy', 'third' => 'osaze']);

        $this->assertSame('kennedy', $waldo->first);
        $this->assertInstanceOf(Bar::class, $waldo->second);
        $this->assertSame('osaze', $waldo->third);
    }

    public function testDefaults()
    {
        $container = new Container;

        $xyzzy = $container->resolve(Xyzzy::class);

        $this->assertSame('test', $xyzzy->default);
    }
}
