## Container

#### Installation
Run `composer install`.

#### Usages

##### Create a binding

```php
<?php

use KennedyOsaze\Container\Container;
use FooInterface;

$container = Container::getInstance();

$container->bind('foo', function ($container) {
    return new Foo();
});

// or
$container->bind(FooInterface::class, function () {
    return new Foo();
})

// or
$container->bind(FooInterface::class, Foo::class);
```

##### Resolve a binding

```php
<?php

$container = Container::getInstance();

$container->get('bar');

$container->resolve('bar');

$container['bar'];
```

##### Create a Singleton

```php
<?php

use KennedyOsaze\Container\Container;
use Foo;

$container = Container::getInstance();

$container->singleton('foo', function ($container) {
    return new Foo();
});

$foo1 = $container->get('foo');
$foo2 = $container->get('foo');

$foo1 === $foo2 // true
```

##### Register a resolved class in the container

```php
<?php

use KennedyOsaze\Container\Container;
use Bar;

$container = Container::getInstance();

$container->instance('bar', new Bar);

$bar1 = $container->get('bar');
$bar2 = $container->get('bar');

$bar1 === $bar2 // true
```

##### Register a binding with parameters that could be resolved from the container

```php
<?php

use KennedyOsaze\Container\Container;

interface FooInterface {}

class Foo interface FooInterface
{
    public function __construct() {}
}

class FooBar
{
    public function __construct(FooInterface $foo, string $name) {}
}

$container = Container::getInstance();

$container->bind(FooInterface::class, Foo::class);

$container->bind('foobar', function ($container) {
    return new FooBar($container->get('foo'), 'dummy_name');
});
```

##### Autowiring

```php
<?php

use KennedyOsaze\Container\Container;

class Foo {}

class Bar {}

class Baz {}

class FooBar
{
    public function __construct(Foo $foo, Bar $bar) {}
}

class FooBarBaz
{
    public function __construct(FooBar $bar, Baz $baz);
}

$container = Container::getInstance();

$container->bind(FooInterface::class, Foo::class);

$fooBarBaz = $container->resolve(FooBarBaz::class);
```
