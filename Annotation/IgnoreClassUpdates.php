<?php

namespace W3C\LifecycleEventsBundle\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class IgnoreClassUpdates
{
}
