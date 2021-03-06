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
use Cssr\MainBundle\Model\Staff;


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
        return array(
            'user' => $this->getUser()
        );
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

        $reports = array();
        switch ( $_GET['type'] ) {

            case '4.0' :
                $result = Report::getFriday40($em,$activeCenter,$areas,$period);
                $reports = $result['reports'];
                $overallAverage = $result['overallAverage'];
                break;

            case 'Meets Expectations' :
                $result = Report::getFridayMeetsExpectations($em,$activeCenter,$areas,$period);
                $reports = $result['reports'];
                $overallAverage = $result['overallAverage'];
                break;

            case 'Caution' :
                $result = Report::getFridayCaution($em,$activeCenter,$areas,$period);
                $reports = $result['reports'];
                $overallAverage = $result['overallAverage'];
                break;

            case 'Challenge' :
                $result = Report::getFridayChallenge($em,$activeCenter,$areas,$period);
                $reports = $result['reports'];
                $overallAverage = $result['overallAverage'];
                break;
        }

        // sorting
        if ( !empty($_GET['sort']) ) {}

        usort($reports,function($a,$b){
            if (strtolower($a['lastname']) === strtolower($b['lastname'])){
                return strnatcmp($a['lastname'],$b['lastname']);
            }
            return strnatcasecmp($a['lastname'],$b['lastname']);
        });

        $vars = array(
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'areas' => $areas,
            'standards' => $standards,
            'reports' => $reports,
            'overallAverage' => $overallAverage
        );

        if ( isset($_GET['type']) ) {
            $vars['type'] = $_GET['type'];
        }

        if ( isset($_GET['comments']) ) {
            $vars['comments'] = true;
        } else {
            $vars['comments'] = false;
        }

        return $vars;
    }

    /**
     * Get Report
     *
     * @Route("/view/friday/comments", name="report_view_friday_comments")
     * @Method("GET")
     * @Template()
     */
    public function viewFridayCommentsAction () {

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

        $reports = Report::getFridayAllComments($em,$activeCenter,$areas,$period);

        // sorting
        usort($reports,function($a,$b){
            if (strtolower($a['lastname']) === strtolower($b['lastname'])){
                return strnatcmp($a['lastname'],$b['lastname']);
            }
            return strnatcasecmp($a['lastname'],$b['lastname']);
        });

        $vars = array(
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'areas' => $areas,
            'standards' => $standards,
            'reports' => $reports
        );

        return $vars;
    }

    /**
     * Lists staff for a report
     *
     * @Route("/caseload/{type}", name="caseload_staff")
     * @Method("GET")
     * @Template()
     */
    public function caseloadStaffAction ( $type )
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        $sql  = 'SELECT U.* ';
        $sql .= 'FROM cssr_user_group UG ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = UG.user_id ';
        $sql .= 'WHERE U.center_id = :centerId AND UG.group_id = :groupId ';
        $sql .= 'ORDER BY U.lastname, U.firstname ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('centerId', $center->id,\PDO::PARAM_INT);
        $stmt->bindValue('groupId', 5, \PDO::PARAM_INT);
        $stmt->execute();

        $staff = $stmt->fetchAll();

        $comments = false;
        switch ( $type ) {

            case 'scores' :
                $report = 'report_caseload_scores';
                break;

            case 'comments' :
                $report = 'report_caseload_scores';
                $comments = true;
                break;

            case 'esp' :
                $report = 'report_caseload_esp';
                break;

            case 'average' :
                $report = 'report_caseload_average';
                break;

            case 'students' :
                $report = 'report_caseload_students';
                break;

            default:
                throw $this->createNotFoundException('Unable to find report.');

        }

        return array(
            'type_name' => Report::getCaseloadReportName($type),
            'type' => $type,
            'staff' => $staff,
            'report' => $report,
            'comments' => $comments
        );
    }

    /**
     * Builds report for a staff member
     *
     * @Route("/caseload/scores/{id}", name="report_caseload_scores")
     * @Method("GET")
     * @Template()
     */
    public function caseloadScoresAction ( $id, $comments = false )
    {
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

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $result = Report::getCaseloadScores($staff,$em,$activeCenter,$areas,$period);
        if ( empty($result) ) {
            $reports = array();
            $overallAverage = 0.0;
        } else {
            $reports = $result['reports'];
            $overallAverage = $result['overallAverage'];
        }

        if ( isset($_GET['comments']) || $comments ) {
            $comments = 1;
            //$reports = Report::getCaseloadComments($staff,$em,$activeCenter,$areas,$period);
            $type = 'comments';
        } else {
            $type = 'scores';
        }

        $vars = array(
            'type_name' => Report::getCaseloadReportName($type),
            'type' => $type,
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'areas' => $areas,
            'standards' => $standards,
            'reports' => $reports,
            'comments' => $comments,
            'staff' => $staff,
            'overallAverage' => $overallAverage
        );

        return $vars;
    }

    /**
     * Builds report for a staff member
     *
     * @Route("/caseload/esp/{id}", name="report_caseload_esp")
     * @Method("GET")
     * @Template()
     */
    public function caseloadEspAction ( $id )
    {
        $type = 'esp';

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        $em = $this->getDoctrine()->getManager();

        $sql = "SELECT DISTINCT(period) period FROM cssr_score ORDER BY period";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $periods = array();
        foreach ( $stmt->fetchAll() as $p ) {
            $periods[] = new \DateTime($p['period']);
        }

        if ( isset($_GET['periodStart']) && isset($_GET['periodEnd']) ) {
            $periodStart = new \DateTime($_GET['periodStart']);
            $periodEnd = new \DateTime($_GET['periodEnd']);
        } else {
            $periodStart = $periods[count($periods)-1];
            $periodEnd = $periods[count($periods)-1];
        }

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $students = Report::getCaseloadEsp($staff,$em,$activeCenter,array('start'=>$periodStart,'end'=>$periodEnd));

        $selectedPeriods = array();
        foreach ( $students as $student ) {
            foreach ( $student['periods'] as $period ) {
                if ( !in_array($period['date'],$selectedPeriods) ) {
                    $selectedPeriods[] = $period['date'];
                }
            }
        }

        // sorting
        usort($students,function($a,$b){
            if (strtolower($a['lastname']) === strtolower($b['lastname'])){
                return strnatcmp($a['lastname'],$b['lastname']);
            }
            return strnatcasecmp($a['lastname'],$b['lastname']);
        });

        $vars = array(
            'type_name' => Report::getCaseloadReportName($type),
            'type' => $type,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periods' => $periods,
            'selectedPeriods' => $selectedPeriods,
            'students' => $students,
            'staff' => $staff
        );

        return $vars;
    }

    /**
     * Builds report for a staff member
     *
     * @Route("/caseload/average/{id}", name="report_caseload_average")
     * @Method("GET")
     * @Template()
     */
    public function caseloadAverageAction ( $id )
    {
        $type = 'average';

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        $em = $this->getDoctrine()->getManager();

        $sql = "SELECT DISTINCT(period) period FROM cssr_score ORDER BY period";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $periods = array();
        foreach ( $stmt->fetchAll() as $p ) {
            $periods[] = new \DateTime($p['period']);
        }

        if ( isset($_GET['periodStart']) && isset($_GET['periodEnd']) ) {
            $periodStart = new \DateTime($_GET['periodStart']);
            $periodEnd = new \DateTime($_GET['periodEnd']);
        } else {
            $periodStart = $periods[count($periods)-1];
            $periodEnd = $periods[count($periods)-1];
        }

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $students = Report::getCaseloadAverage($staff,$em,$activeCenter,array('start'=>$periodStart,'end'=>$periodEnd));

        $selectedPeriods = array();
        foreach ( $students as $student ) {
            foreach ( $student['periods'] as $period ) {
                if ( !in_array($period['date'],$selectedPeriods) ) {
                    $selectedPeriods[] = $period['date'];
                }
            }
            break;
        }

        // sorting
        usort($students,function($a,$b){
            if (strtolower($a['lastname']) === strtolower($b['lastname'])){
                return strnatcmp($a['lastname'],$b['lastname']);
            }
            return strnatcasecmp($a['lastname'],$b['lastname']);
        });

        $vars = array(
            'type_name' => Report::getCaseloadReportName($type),
            'type' => $type,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periods' => $periods,
            'selectedPeriods' => $selectedPeriods,
            'students' => $students,
            'staff' => $staff
        );

        return $vars;
    }

    /**
     * Builds report for a staff member
     *
     * @Route("/caseload/students/{id}", name="report_caseload_students")
     * @Method("GET")
     * @Template()
     */
    public function caseloadStudentsAction ( $id )
    {
        $type = 'students';

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        $em = $this->getDoctrine()->getManager();

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $students = Staff::getStudents($em,$staff->getId());

        $vars = array(
            'type_name' => Report::getCaseloadReportName($type),
            'type' => $type,
            'staff' => $staff,
            'students' => $students
        );

        return $vars;
    }

    /**
     * Builds report for a staff member
     *
     * @Route("/caseload/students/{id}/report", name="report_caseload_students_report")
     * @Method("GET")
     * @Template()
     */
    public function caseloadStudentsReportAction ( $id )
    {
        $type = 'students';

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        $em = $this->getDoctrine()->getManager();

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $studentIds = $_GET['students'];

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();


        $students = Report::getCaseloadStudents($em,$staff,$areas,$studentIds);

        usort($students,function($a,$b){
            if (strtolower($a['lastname']) === strtolower($b['lastname'])){
                return strnatcmp($a['lastname'],$b['lastname']);
            }
            return strnatcasecmp($a['lastname'],$b['lastname']);
        });

        $vars = array(
            'comments' => true,
            'areas' => $areas,
            'standards' => $standards,
            'students' => $students,
            'type_name' => Report::getCaseloadReportName($type),
            'type' => $type,
            'staff' => $staff
        );

        return $vars;
    }

    /**
     * Builds report for a staff member
     *
     * @Route("/students/{id}/record", name="report_student_record")
     * @Method("GET")
     * @Template()
     */
    public function studentRecordReportAction ( $id ) {

        $em = $this->getDoctrine()->getManager();

        $student = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Unable to find Student entity.');
        }

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $report = Report::getStudentRecord($em,$areas,$id);

        $vars = array(
            'comments' => true,
            'areas' => $areas,
            'standards' => $standards,
            'student' => $student,
            'report' => $report
        );

        return $vars;
    }

    /**
     * Lists students
     *
     * @Route("/history/student", name="history_student")
     * @Method("GET")
     * @Template()
     */
    public function historyStudentAction ()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        $sql  = 'SELECT U.* ';
        $sql .= 'FROM cssr_user_group UG ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = UG.user_id ';
        $sql .= 'WHERE U.center_id = :centerId AND UG.group_id = :groupId AND U.enabled = :enabled ';
        $sql .= 'ORDER BY U.lastname, U.firstname, U.middlename ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('centerId', $center->id,\PDO::PARAM_INT);
        $stmt->bindValue('groupId', 6, \PDO::PARAM_INT);
        $stmt->bindValue('enabled', 1, \PDO::PARAM_INT);
        $stmt->execute();

        $students = $stmt->fetchAll();

        if ( isset($_GET['comments']) ) {
            $reportName = 'history_student_comments';
            $comments = true;
        } else {
            $reportName = 'history_student_scores';
            $comments = false;
        }

        return array(
            'comments' => $comments,
            'reportName' => $reportName,
            'students' => $students
        );
    }

    /**
     * Student history scores
     *
     * @Route("/history/student/{id}/scores", name="history_student_scores")
     * @Method("GET")
     * @Template()
     */
    public function historyStudentScoresAction ( $id )
    {
        $em = $this->getDoctrine()->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $student = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Unable to find Student.');
        }

        $result = Report::getHistoryStudent($em,$areas,$student);
        if ( empty($result) ) {
            $result['reports'] = array();
            $result['overallAverage'] = 0.0;
        }

        return array(
            'user' => $this->getUser(),
            'areas' => $areas,
            'standards' => $standards,
            'history' => $result['reports'],
            'student' => $student,
            'overallAverage' => $result['overallAverage']
        );
    }

    /**
     * Student history comments
     *
     * @Route("/history/student/{id}/comments", name="history_student_comments")
     * @Method("GET")
     * @Template()
     */
    public function historyStudentCommentsAction ( $id )
    {
        $em = $this->getDoctrine()->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $student = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Unable to find Student.');
        }

        $report = Report::getHistoryStudentComments($em,$areas,$student);

        return array(
            'user' => $this->getUser(),
            'areas' => $areas,
            'standards' => $standards,
            'student' => $report
        );
    }

    /**
     * Lists staff
     *
     * @Route("/history/staff", name="history_staff")
     * @Method("GET")
     * @Template()
     */
    public function historyStaffAction ()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        $sql  = 'SELECT U.* ';
        $sql .= 'FROM cssr_user_group UG ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = UG.user_id ';
        $sql .= 'WHERE U.center_id = :centerId AND UG.group_id = :groupId ';
        $sql .= 'ORDER BY U.lastname, U.firstname ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('centerId', $center->id,\PDO::PARAM_INT);
        $stmt->bindValue('groupId', 5, \PDO::PARAM_INT);
        $stmt->execute();
        $staff = $stmt->fetchAll();

        if ( isset($_GET['comments']) ) {
            $reportName = 'history_staff_comments';
            $comments = true;
        } else {
            $reportName = 'history_staff_scores';
            $comments = false;
        }

        return array(
            'comments' => $comments,
            'reportName' => $reportName,
            'staff' => $staff
        );
    }

    /**
     * Staff history scores
     *
     * @Route("/history/staff/{id}/scores", name="history_staff_scores")
     * @Method("GET")
     * @Template()
     */
    public function historyStaffScoresAction ( $id )
    {
        $em = $this->getDoctrine()->getManager();

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Unable to find Staff.');
        }

        // get staff courses
        $sql  = 'SELECT C.id, A.id, A.name FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'WHERE C.user_id = '.$id;
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $course = $stmt->fetch();

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

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

        $report = Report::getHistoryStaffScores($staff,$em,$activeCenter,$areas,$period);

        if ( empty($report) ) {
            $report['reports'] = array();
            $report['overallAverage'] = 0.0;
        }

        $vars = array(
            'staff' => $staff,
            'course' => $course,
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'areas' => $areas,
            'standards' => $standards,
            'reports' => $report['reports'],
            'overallAverage' => $report['overallAverage']
        );

        if ( isset($_GET['type']) ) {
            $vars['type'] = $_GET['type'];
        }

        if ( isset($_GET['comments']) ) {
            $vars['comments'] = true;
        } else {
            $vars['comments'] = false;
        }

        return $vars;
    }

    /**
     * Staff history comments
     *
     * @Route("/history/staff/{id}/comments", name="history_staff_comments")
     * @Method("GET")
     * @Template()
     */
    public function historyStaffCommentsAction ( $id )
    {
        $em = $this->getDoctrine()->getManager();

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Unable to find Staff.');
        }

        // get staff courses
        $sql  = 'SELECT C.id, A.id, A.name FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'WHERE C.user_id = '.$id;
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $course = $stmt->fetch();

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

        $reports = Report::getHistoryStaffComments($staff,$em,$activeCenter,$areas,$period);

        // sorting
        usort($reports,function($a,$b){
            if (strtolower($a['lastname']) === strtolower($b['lastname'])){
                return strnatcmp($a['lastname'],$b['lastname']);
            }
            return strnatcasecmp($a['lastname'],$b['lastname']);
        });

        $vars = array(
            'staff' => $staff,
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'areas' => $areas,
            'standards' => $standards,
            'reports' => $reports,
            'course' => $course
        );

        return $vars;
    }

    /**
     * Staff exception
     *
     * @Route("/exception", name="staff_exception")
     * @Method("GET")
     * @Template()
     */
    public function staffExceptionAction ()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        if (!$activeCenter) {
            throw $this->createNotFoundException('Unable to find current center.');
        }

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

        // get staff

        $staff = Staff::getStaffWithCaseload($em,$activeCenter->id,$period->format('Y-m-d'));

        foreach ( $staff as $s ) {
            if ( !$s['scoreCount'] || $s['scoreCount'] < count($s['students']) ) {
                $report[] = array(
                    'id' => $s['id'],
                    'firstname' => $s['firstname'],
                    'lastname' => $s['lastname'],
                    'middlename' => $s['middlename'],
                    'course_name' => $s['course_name'],
                    'studentCount' => count($s['students']),
                    'scoreCount' => $s['scoreCount']
                );
            }
        }

        $vars = array(
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'report' => $report
        );

        return $vars;
    }

    /**
     * Weekly stats
     *
     * @Route("/statistics", name="report_statistics")
     * @Method("GET")
     * @Template()
     */
    public function weeklyStatisticsAction ()
    {
        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        $em = $this->getDoctrine()->getManager();

        $sql = "SELECT DISTINCT(period) period FROM cssr_score ORDER BY period";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $periods = array();
        foreach ( $stmt->fetchAll() as $p ) {
            $periods[] = new \DateTime($p['period']);
        }

        if ( isset($_GET['periodStart']) && isset($_GET['periodEnd']) ) {
            $periodStart = new \DateTime($_GET['periodStart']);
            $periodEnd = new \DateTime($_GET['periodEnd']);
        } else {
            $periodStart = $periods[count($periods)-1];
            $periodEnd = $periods[count($periods)-1];
        }


        $reports = array();
        if ( $activeCenter->id > 0 ) {
            $reports[] = Report::getWeeklyStatistics($em,$activeCenter->id,$periodStart,$periodEnd);
        } else {

            $centers = $em->getRepository('CssrMainBundle:Center')->findBy(
                array('active' => 1),
                array('name' => 'ASC')
            );
            foreach ( $centers as $center ) {
                $reports[] = Report::getWeeklyStatistics($em,$center->getId(),$periodStart,$periodEnd);
            }
        }

        $vars = array(
            'center' => $activeCenter,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periods' => $periods,
            'reports' => $reports
        );

        return $vars;

    }
}
