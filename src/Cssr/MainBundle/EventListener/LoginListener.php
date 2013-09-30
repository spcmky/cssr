<?php
namespace CSSR\MainBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class LoginListener implements EventSubscriberInterface
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN => 'onSecurityImplicitLogin',
        );
    }

    public function onSecurityImplicitLogin(FormEvent $event)
    {
        $point = $this->container->get('process_points');
        $point->ProcessPoints(1 , $this->container);
    }
}