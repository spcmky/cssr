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
 * Center controller.
 *
 * @Route("/message")
 */
class MessageController extends Controller
{

    /**
     * Lists Messages
     *
     * @Route("/", name="message")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * Get Message
     *
     * @Route("/view", name="message_view")
     * @Method("GET")
     * @Template()
     */
    public function viewAction()
    {
        $em = $this->getDoctrine()->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $scores = array();
        for ( $i = 0; $i < 25; $i++ ) {
            $scores[$i] = array();

            $scores[$i][0] = uniqid().', '.uniqid();

            $total = 0;
            $units = 0;
            for ( $j = 1; $j < 20; $j++ ) {
                if ( rand(1,5) == 1 ) {
                    $total += $scores[$i][$j] = rand(0,5);
                    $units++;
                } else {
                    $scores[$i][$j] = null;
                }
            }

            $scores[$i][23] = $total; // total units
            $scores[$i][24] = (!$units)? 0 : round(($total/$units),1); // average
            $scores[$i][25] = 'Gold'; // status
        }

        return array(
            'areas' => $areas,
            'standards' => $standards,
            'scores' => $scores
        );
    }




}
