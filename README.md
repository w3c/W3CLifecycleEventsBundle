lifecycle-events-bundle
=======================

This Symfony bundle is meant to capture and dispatch events that happen throughout the lifecycle of entities
- creation
- deletion
- updates

Doctrine already provides such events (this bundle actually relies on Doctrine's postPersist, postRemove and preUpdate ones), but using them directly has a few shortcomings:
- You don't decide at which point in a action you want to dispatch events. Events are fired during a flush.
- You cannot temporarily prevent Doctrine events from being fired or listened
- When Doctrine's events are fired, you are not assured that the entities have actually been saved in the database. This is obvious for preUpdate (sent before persisting the changes), but postPersist and postRemove have the same issue: if you persist two new entities in a single transaction, the first insert could work (thus an event would be sent) but not the second, resulting in no entities being saved at all

This bundle aims at circumventing these issues by providing means to fire entity creation, deletion and update events when the developer needs to (for example after a successful flush). He can also modify, add or remove some.

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

You can use the bundle through the service W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher.
Here is how you get it from a controller, and how you would fire all the events it has recorded:
```
$dispatcher = $this->container->get("w3c_lifecycle_events.dispatcher");
$dispatcher->dispatchEvents();
```

The sevice can dispatch three different events:
- w3c.lifecycle.created as an instance of W3C\LifecycleEventsBundle\Event\LifecycleEvent
- w3c.lifecycle.deleted as an instance of W3C\LifecycleEventsBundle\Event\LifecycleEvent
- w3c.lifecycle.updated as an instance of W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent, which extends LifecycleEvent

When a LifecycleEvent is fired, you can retrieve its associated entity (that has been created or deleted).
When a LifecycleUpdateEvent is fired, you can retrieve its associated entity (that has been updated) and also query the list of changes this object has undergone. The methods are similar to [Doctrine\ORM\Event\PreUpdateEventArgs](http://www.doctrine-project.org/api/orm/2.3/class-Doctrine.ORM.Event.PreUpdateEventArgs.html "Doctrine API for PreUpdateEventsArgs")

If you need to add, remove of modify some of the events that are going to be fired, you can use the following methods:
```
$creations = $dispatcher->getCreations();
$deletions = $dispatcher->getDeletions();
$updates = $dispatcher->getUpdates();
```
These return ArrayLists of events. You can add, remove or modify elements as you like.
