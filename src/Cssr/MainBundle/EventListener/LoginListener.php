<?php
namespace CSSR\MainBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use FOS\UserBundle\Event\UserEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class LoginListener implements EventSubscriberInterface
{
    private $container;

    public function __construct ( Container $container ) {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents() {
        return array(
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin'
        );
    }

    public function onImplicitLogin ( UserEvent $event ) {
        $user = $event->getUser();
        $this->updateUserSession($user);
    }

    public function onSecurityInteractiveLogin ( InteractiveLoginEvent $event ) {
        $user = $event->getAuthenticationToken()->getUser();
        $this->updateUserSession($user);
    }

    private function updateUserSession ( $user ) {
        // get the user's center and save it to the session.
        $centerEntity = $user->getCenter();

        $logger = $this->container->get('logger');

        if ( $centerEntity ) {
            $center = new \stdClass();
            $center->id = $centerEntity->getId();
            $center->name = $centerEntity->getName();

            $logger->debug('OnLogin: Setting user center '.$center->id);

        } else {
            $center = new \stdClass();
            $center->id = null;
            $center->name = 'Select Center';

            $logger->debug('OnLogin: User has no center affiliation');
        }

        // get session service
        $session = $this->container->get('session');
        $session->set('center', $center);
    }

}