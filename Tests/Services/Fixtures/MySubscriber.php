<?php

namespace W3C\LifecycleEventsBundle\Tests\Services\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

class MySubscriber implements EventSubscriberInterface
{

    private $ran = false;

    private $dispatcher;
    private $annotation;
    private $args;

    public function __construct(LifecycleEventsDispatcher $dispatcher, $annotation, $args)
    {
        $this->dispatcher = $dispatcher;
        $this->annotation = $annotation;
        $this->args       = $args;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            LifecycleEvents::CREATED => 'onCalled',
            LifecycleEvents::DELETED => 'onCalled',
            LifecycleEvents::UPDATED => 'onCalled',
            LifecycleEvents::PROPERTY_CHANGED => 'onCalled',
            LifecycleEvents::COLLECTION_CHANGED => 'onCalled',
        ];
    }

    public function onCalled()
    {
        if (!$this->ran) {
            $this->ran = true; // we don't want to run in an infinite loop
            $this->dispatcher->addCreation(new Create(), $this->args);
            $this->dispatcher->dispatchEvents();
        }
    }
}