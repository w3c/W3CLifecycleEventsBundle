lifecycle-events-bundle
=======================

This Symfony bundle is meant to capture and dispatch events that happen throughout the lifecycle of entities:
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

For basic use, everything is automated: after a successful flush, all the events get fired at once.

The sevice can dispatch three different events:
- w3c.lifecycle.created as an instance of W3C\LifecycleEventsBundle\Event\LifecycleEvent
- w3c.lifecycle.deleted as an instance of W3C\LifecycleEventsBundle\Event\LifecycleEvent
- w3c.lifecycle.updated as an instance of W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent, which extends LifecycleEvent

When a LifecycleEvent is fired, you can retrieve its associated entity (that has been created or deleted).
When a LifecycleUpdateEvent is fired, you can retrieve its associated entity (that has been updated) and also query the list of changes this object has undergone. The methods are similar to [Doctrine\ORM\Event\PreUpdateEventArgs](http://www.doctrine-project.org/api/orm/2.3/class-Doctrine.ORM.Event.PreUpdateEventArgs.html "Doctrine API for PreUpdateEventsArgs")

To use the events that are fired, you will need a listener. Such listeners can be found on the [demo bundle](https://github.com/w3c/lifecycle-events-demo-bundle)

Disabling automatic dispatching of events
-----------------------------------------

There are two ways to disable the automatic dispatch:
- globally in config.yml
``` yaml
w3_c_lifecycle_events:
    auto_dispatch:        false
```
- temporarily in a container:
```
$dispatcher = $this->container->get("w3c_lifecycle_events.dispatcher");
$dispatcher->setAutoDispatch(false);
$dispatcher->dispatchEvents(); // manually dispatch all events
```

Manipulating events before they are sent
----------------------------------------

There are two ways to add, remove of modify some of the events that are going to be fired.

### On-demand

You have to disable automatic dispatch first and then use the following methods:
```
$creations = $dispatcher->getCreations();
$deletions = $dispatcher->getDeletions();
$updates = $dispatcher->getUpdates();
```
These return ArrayLists of events. You can add, remove or modify elements as you like.

When done, you can dispatch all the events by calling
 ```
 $dispatcher->dispatchEvents()
 ```

### Automatically

If you need to always make the same changes to the events, you can register a new event listener listening to
```w3c.lifecycle.preAutoDispatch```. This event is fired right before dispatching all the events and only when
```auto_dispatch``` is set to true.
This event has one method getDispatcher() that returns the same dispatcher as in the previous section, thus allowing
to retrieve creation deletion and update events, and manipulate them.