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

