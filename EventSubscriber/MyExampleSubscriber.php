<?php

namespace Drupal\oeaw\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class MyExampleSubscriber implements EventSubscriberInterface {
    /**
     * @param GetResponseEvent $event
     */
    public function checkForRedirection(GetResponseEvent $event) {
        if ('/oeaw_newresource_one' == $event->getRequest()->getPathInfo()) {
            error_log('oeaw_newresource_one');
            \EasyRdf\RdfNamespace::set('dct', 'http://purl.org/dc/terms/');
        }
        if ('/oeaw_multi_new_resource' == $event->getRequest()->getPathInfo()) {
            error_log('oeaw_multi_new_resource');
            \EasyRdf\RdfNamespace::set('dct', 'http://purl.org/dc/terms/');
        }
    }

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents() {
        //$events[KernelEvents::REQUEST][] = array('checkForRedirection');
        //return $events;
    }
}
