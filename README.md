[![Build Status](https://github.com/w3c/W3CLifecycleEventsBundle/actions/workflows/symfony.yml/badge.svg)](https://github.com/w3c/W3CLifecycleEventsBundle/actions/workflows/symfony.yml)
[![Coverage Status](https://coveralls.io/repos/github/w3c/W3CLifecycleEventsBundle/badge.svg?branch=master)](https://coveralls.io/github/w3c/W3CLifecycleEventsBundle?branch=master)
[![SensioLabsInsight](https://insight.symfony.com/projects/7634f8ca-5b9c-48bb-8443-2730c646c095/mini.png)](https://insight.symfony.com/projects/7634f8ca-5b9c-48bb-8443-2730c646c095)

lifecycle-events-bundle
=======================

This Symfony bundle is meant to capture and dispatch events that happen throughout the lifecycle of entities:
- creation
- deletion
- updates

Doctrine already provides such events, but using them directly has a few shortcomings:
- You don't decide at which point in a action you want to dispatch events. Events are fired during a flush.
- When Doctrine events are fired, you are not assured that the entities have actually been saved in the database. 
This is obvious for preUpdate (sent before persisting the changes), but postPersist and preRemove have the same issue: 
if you persist two new entities in a single transaction, the first insert could work (thus an event would be sent) but 
not the second, resulting in no entities being saved at all

This bundle aims at circumventing these issues by providing means to fire entity creation, deletion and update events 
after a successful flush or whenever needed.

It also provides a set of annotation to configure what events should be sent and when.

This bundle was partially inspired by @kriswallsmith's talk
["Matters of State"](https://www.youtube.com/watch?v=lEiwP4w6mf4).

Installation
------------

Simply run assuming you have installed composer.phar or composer binary:

``` bash
$ php composer.phar require w3c/lifecycle-events-bundle 1.0.*
```

Finally, enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new W3C\LifecycleEventsBundle\W3CLifecycleEventsBundle(),
    );
}
```
That's it!

Usage
-----

### Annotations

For this bundle to do anything interesting, it is necessary to annotate entities you want to monitor.
There are five annotations. Three of them apply to classes (`@Create`, `@Delete` and `@Update`) and the remaining two to
properties (`@Change`, `@IgnoreClassUpdates`).

All annotations live in the namespace `W3C\LifecycleEventsBundle\Annotation`, so it is recommended to import it:
``` php
<?php
use W3C\LifecycleEventsBundle\Annotation as On;
```

#### `@On\Create` 

Monitors the creation of new entities. It accepts the following parameters:
- `event`: the event being sent every time an entity is created (`w3c.lifecycle.created` by default)
- `class`: the class of this event (`W3C\LifecycleEventsBundle\Event\LifecycleEvent` by default). This class
must have a constructor with the following signature:

``` php
<?php
/**
 * @param mixed $entity the entity being created
 */
public function __construct($entity)
```

#### `@On\Delete`

Monitors the deletion (or soft deletion, if you use Doctrine Extensions) of entities. It accepts the following parameters:
- `event`: the event being sent every time an entity is deleted (`w3c.lifecycle.deleted` by default)
- `class`: the class of this event (`W3C\LifecycleEventsBundle\Event\LifecycleEvent` by default). This class
must have a constructor with the following signature:

``` php
<?php
/**
 * @param mixed $entity the entity being deleted
 * @param array $identifier identifier of the entity being deleted
 */
public function __construct($entity, $identifier)
```

#### `@On\Update`

Monitors updates to entities. It accepts the following parameters:
- `event`: the event being sent (`w3c.lifecycle.updated` by default) every time an entity is updated
- `class`: the class of this event (`W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent` by default). This class
must have a constructor with the following signature: 

``` php
<?php
/**
 * @param object $entity the entity being modified
 * @param array $propertiesChangeSet list of changes to properties
 * @param array $collectionsChangeSet list of changes to collections
 */
public function __construct($entity, array $propertiesChangeSet = null, array $collectionsChangeSet = null)
```
- `monitor_collections`: whether the annotation should monitor changes to collection fields. Defaults to true
- `monitor_owning`: whether owning side relationship changes should be also monitored as inverse side changes. Defaults to false

#### `@On\Change`

Monitors whenever an entity field (property or collection) changes. It accepts the following parameters:
- `event`: the event being sent (`w3c.lifecycle.property_changed` or `w3c.lifecycle.collection_changed` by default) 
every time an entity is updated
- `class`: the class of this event (`W3C\LifecycleEventsBundle\Event\LifecyclePropertyChangedEvent` by default if put on
a regular property, `W3C\LifecycleEventsBundle\Event\LifecycleCollectionChangedEvent` when put on a collection).
This class must have a constructor with the following signature for regular properties: 

``` php
<?php
/**
 * @param object $entity entity being modified
 * @param string $property property being modified
 * @param array $oldValue property's old value
 * @param array $newValue property's new value
 */
public function __construct($entity, $property, $oldValue = null, $newValue = null)
```
and for collections:

``` php
<?php
/**
 * @param object $entity entity being modified
 * @param string $property collection being modified
 * @param array $deletedElements elements being deleted from the collection
 * @param array $insertedElements elements being inserted to the collection
 */
public function __construct($entity, $property, $deletedElements = null, $insertedElements = null)
```
- `monitor_owning`: whether to record changes to this field when owning sides change (defaults to `false`). Using
`@On\Change` on inverse side of relationships won't trigger any events unless this paramter is set to true. This
parameter is likely to be removed in the next major version and act as if it was set to `true` since when the 
annotation is added to the inverse side of relationship, it is obvious it means that you want changes to owning side to
be monitored here

#### `@On\IgnoreClassUpdates`

This annotation is a bit different. When placed on a field (property or collection), it prevents `@On\Update` from 
firing events related to this field. `@On\Change` ones will still work. This annotation does not allow any parameters.

#### Example class

``` php
<?php
/**
 * Person
 *
 * @ORM\Table(name="person")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PersonRepository")
 * @On\Create(
 *     PersonEvents::CREATED,
 *     class=PersonEvent::class
 * )
 * @On\Delete(
 *     PersonEvents::DELETED,
 *     class=PersonEvent::class
 * )
 * @On\Update(PersonEvents::UPDATED)
 */
class Person
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @On\Change(PersonEvents::PROPERTY_CHANGED)
     */
    private $name;

    /**
     * @var Person[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Person")
     * @ORM\JoinTable(name="friendships",
     *      joinColumns={@ORM\JoinColumn(name="person_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="friend_id", referencedColumnName="id")}
     *      )
     * @On\Change()
     * @On\IgnoreClassUpdates()
     */
    private $friends;

    [...]   
}
```

With such a class, the following events will be fired:
- `PersonEvents::CREATED` when a Person is created, with the class `PersonEvent`
- `PersonEvents::DELETED` when a Person is deleted, with the class `PersonEvent`
- `PersonEvents::UPDATED` when a Person is updated, with the  class `LifecycleUpdatedEvent`, but will not record changes
to `Person::$friends`
- `PersonEvents::PROPERTY_CHANGED` when Person::$name changes, with the class `LifecyclePropertyChangedEvent`
- `w3c.lifecycle.collection_changed` when Person::$friends changes, with the class `LifecycleCollectionChangedEvent`

Disabling automatic dispatching of events
-----------------------------------------

Lifecycle events are dispatched by default after a successful flush. If needed, this can be disabled:
- globally in config.yml
``` yaml
w3_c_lifecycle_events:
    auto_dispatch:        false
```
- temporarily in a container (before Symfony 4):

``` php
<?php
$dispatcher = $this->container->get("w3c_lifecycle_events.dispatcher");
$dispatcher->setAutoDispatch(false);
```

- temporarily in a container (since Symfony 4):

``` php
<?php
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
public function testAction(LifecycleEventsDispatcher $dispatcher)
{
    [...]
    $dispatcher->setAutoDispatch(false);
    [...]
}
```

Events can then be dispatched manually using the following:
``` php
<?php
$dispatcher->dispatchEvents(); // manually dispatch all events
```

Special case: inheritance
-------------------------

If you use inheritance in your entities, make sure to set fields of the parent class(es) protected (or public) so that
changes to those can be monitored as belonging to subclasses.

Failing to do so may lead to `\ReflectionException` exceptions such as:
```
Property W3CGroup::$updated does not exist. Could this be a private field of a parent class?
```
Even if those fields are not monitored!
