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
        $em = $this->getDoctrine()->getManager();

        $criteria = array('active'=>1);
        $session = $this->getRequest()->getSession();
        $center = $session->get('center');
        if ( $center->id > 0 ) {
            $criteria['center'] = $center->id;
        }

        $user = $this->getUser();
        $groupIds = array();
        foreach ( $user->getGroups() as $group ) {
            $groupIds[] = $group->getId();
        }

        // find all messages for the current center
        $sql = 'SELECT message_id ';
        $sql .= 'FROM cssr_group_message GM ';
        $sql .= 'WHERE GM.group_id IN ('.implode(',',$groupIds).') ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $messageIds = array();
        foreach ( $stmt->fetchAll() as $m ) {
            $messageIds[] = $m['message_id'];
        }

        $messages = $em->getRepository('CssrMainBundle:Message')->findBy(
            $criteria,
            array('updated'=>'desc')
        );

        $userMessages = array();
        foreach ( $messages as $message ) {
            if ( in_array($message->getId(),$messageIds) ) {
                $userMessages[] = $message;
            }
        }

        return array(
            'user' => $user,
            'center' => $center,
            'messages' => $userMessages
        );
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
