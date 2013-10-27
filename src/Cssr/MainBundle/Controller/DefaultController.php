<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Default controller.
 *
 * @Route("/")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * Get global menu
     *
     * @Route("/menu", name="default_menu")
     * @Method("GET")
     * @Template("CssrMainBundle:Default:menu.html.twig")
     */
    public function showMenuAction()
    {
        $session = $this->getRequest()->getSession();
        $center = $session->get('center');
        if (!$center) {
            $center = new \stdClass();
            $center->id = null;
            $center->name = 'All Centers';
        }

        $user = $this->getUser();

        $userGroupIds = array();
        $admin = 0;
        foreach ( $user->getGroups() as $group ) {
            $userGroupIds[] = $group->getId();
            if ( $group->getId() < 5 ) {
                $admin = 1;
            }
        }

        return array(
            'admin' => $admin,
            'user' => $user,
            'userGroupIds' => $userGroupIds,
            'center' => $center
        );
    }
}
