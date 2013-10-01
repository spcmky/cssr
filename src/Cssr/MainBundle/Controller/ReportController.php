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

        $sql = "SELECT DISTINCT(UNIX_TIMESTAMP(period)) period FROM cssr_score";
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

        $sql = "SELECT DISTINCT(period) period FROM cssr_score ORDER BY period";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $periods = array();
        foreach ( $stmt->fetchAll() as $p ) {
            $periods[] = new \DateTime($p['period']);
        }

        if ( isset($_GET['period']) ) {
            $period = new \DateTime($_GET['period']);
        } else {
            $period = $periods[count($periods)-1];
        }

        $period_start = clone $period;
        $period_start->sub(new \DateInterval('P1D'));

        $period_end = clone $period;
        $period_end->add(new \DateInterval('P5D'));

        $reports = null;
        switch ( $_GET['type'] ) {

            case '4.0' :
                $reports = Report::getFriday40($em,$activeCenter,$areas,$period);
                break;

            case 'Meets Expectations' :
                $reports = Report::getFridayMeetsExpectations($em,$activeCenter,$areas,$period);
                break;

            case 'Caution' :
                $reports = Report::getFridayCaution($em,$activeCenter,$areas,$period);
                break;

            case 'Challenge' :
                $reports = Report::getFridayChallenge($em,$activeCenter,$areas,$period);
                break;
        }

        $vars = array(
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
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
