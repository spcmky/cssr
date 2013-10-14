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
            'reports' => $reports
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

        if ( isset($_GET['comments']) || $comments ) {
            $reports = Report::getCaseloadComments($staff,$em,$activeCenter,$areas,$period);
            $type = 'comments';
        } else {
            $reports = Report::getCaseloadScores($staff,$em,$activeCenter,$areas,$period);
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
            'staff' => $staff
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

        $vars = $this->caseloadEspAction($id);

        $vars['type_name'] = Report::getCaseloadReportName($type);
        $vars['type'] = $type;

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

        $sql  = 'SELECT C.id, A.name FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'WHERE C.user_id = '.$id;
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $courses = $stmt->fetchAll();

        $courseIds = array();
        foreach ( $courses as $c ) {
            $courseIds[] = $c['id'];
        }

        $sql  = 'SELECT S.id, S.firstname, S.lastname, S.middlename FROM cssr_student_course SC ';
        $sql .= 'LEFT JOIN cssr_user S ON S.id = SC.student_id ';
        $sql .= 'WHERE SC.course_id IN ('.implode(',',$courseIds).') ';
        $sql .= 'ORDER BY S.lastname, S.firstname ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

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
        $sql .= 'WHERE U.center_id = :centerId AND UG.group_id = :groupId ';
        $sql .= 'ORDER BY U.lastname, U.firstname ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('centerId', $center->id,\PDO::PARAM_INT);
        $stmt->bindValue('groupId', 6, \PDO::PARAM_INT);
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

        $report = Report::getHistoryStudent($em,$areas,$student);

        return array(
            'areas' => $areas,
            'standards' => $standards,
            'student' => $report
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

        $reports = Report::getHistoryStaffScores($staff,$em,$activeCenter,$areas,$period);

        usort($reports,function($a,$b){
            if (strtolower($a['lastname']) === strtolower($b['lastname'])){
                return strnatcmp($a['lastname'],$b['lastname']);
            }
            return strnatcasecmp($a['lastname'],$b['lastname']);
        });

        $vars = array(
            'staff' => $staff,
            'course' => $course,
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

        $sql = "SELECT U.id, U.lastname, U.firstname, U.middlename, A.name AS course_name, s.period, COUNT(S.id) score_count
        FROM cssr_user U
        LEFT JOIN cssr_user_group UG ON UG.user_id = U.id
        LEFT JOIN cssr_group G ON G.id = UG.group_id
        INNER JOIN cssr_course C ON C.user_id = U.id
        LEFT JOIN cssr_area A ON A.id = C.area_id
        LEFT JOIN cssr_score S ON S.course_id = C.id
        WHERE G.id = 5 AND U.center_id = ".$activeCenter->id."
        GROUP BY U.id, S.period
        ORDER BY U.lastname";

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $staff = $stmt->fetchAll();

        // score all periods
        $scored = array();
        foreach ( $staff as $user ) {
            if ( !isset($scored[$user['id']]) ) {
                $scored[$user['id']] = array(
                    'id' => $user['id'],
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'middlename' => $user['middlename'],
                    'course_name' => $user['course_name'],
                    'periods' => array()
                );
            }

            foreach ( $periods as $date ) {
                $user_period = new \DateTime($user['period']);
                if ( $user_period->format('Y-m-d') == $date->format('Y-m-d') ) {
                    $scored[$user['id']]['periods'][$date->format('Y-m-d')] = $user['score_count'];
                } else if ( !isset($scored[$user['id']]['periods'][$date->format('Y-m-d')]) ) {
                    $scored[$user['id']]['periods'][$date->format('Y-m-d')] = 0;
                }
            }
        }


        $report = array();
        foreach ( $scored as $score ) {
            if ( $score['periods'][$period->format('Y-m-d')] == 0 ) {
                $report[] = array(
                    'id' => $score['id'],
                    'firstname' => $score['firstname'],
                    'lastname' => $score['lastname'],
                    'middlename' => $score['middlename'],
                    'course_name' => $score['course_name']
                );
            }
        }

        //echo "<pre>".print_r($report,true)."</pre>"; die();

        $vars = array(
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'report' => $report
        );

        return $vars;
    }
}
