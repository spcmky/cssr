<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Cssr\MainBundle\Model\Report;

/**
 * Center controller.
 *
 * @Route("/report")
 */
class ReportController extends Controller
{

    /**
     * Lists Reports
     *
     * @Route("/", name="report")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * Get Report
     *
     * @Route("/view", name="report_view")
     * @Method("GET")
     * @Template()
     */
    public function viewAction()
    {
        $em = $this->getDoctrine()->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $sql = "SELECT DISTINCT(period) period FROM cssr_score";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $periods = $stmt->fetchAll();

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

            // status
            if ( $scores[$i][24] >= 4.3 ) {
                $scores[$i][25] = 'Gold';
            } else if ( $scores[$i][24] >= 3.5 ) {
                $scores[$i][25] = 'Green';
            } else if ( $scores[$i][24] >= 3.0 ) {
                $scores[$i][25] = 'Blue';
            } else {
                $scores[$i][25] = 'None';
            }
        }


        return array(
            'periods' => $periods,
            'areas' => $areas,
            'standards' => $standards,
            'scores' => $scores
        );
    }

    /**
     * Get Report
     *
     * @Route("/view/friday", name="report_view_friday")
     * @Method("GET")
     * @Template()
     */
    public function viewFridayAction () {

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        $em = $this->getDoctrine()->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $sql = "SELECT DISTINCT(period) period FROM cssr_score";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $periods = $stmt->fetchAll();


        /**
         * 4.0 Report - avgScore >= 4
         * 3.0 Report - There are no 1 or 2 scores
         * Caution Report - no 1 scores and one 2 score
         * Challenge Report - one or more 1 scores OR one or more 2 scores
         */
        $reports = null;
        switch ( $_GET['type'] ) {

            case '4.0' :
                $reports = Report::getFriday40($em,$activeCenter,$areas);
                break;

            case 'Meets Expectations' :
                $reports = Report::getFridayMeetsExpectations($em,$activeCenter,$areas);
                break;

            case 'Caution' :
                $reports = Report::getFridayCaution($em,$activeCenter,$areas);
                break;

            case 'Challenge' :
                $reports = Report::getFridayChallenge($em,$activeCenter,$areas);
                break;
        }

        $vars = array(
            'periods' => $periods,
            'areas' => $areas,
            'standards' => $standards,
            'reports' => $reports
        );

        if ( isset($_GET['type']) ) {
            $vars['type'] = $_GET['type'];
        }

        return $vars;
    }



}
