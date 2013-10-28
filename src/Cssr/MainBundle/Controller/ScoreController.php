<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Cssr\MainBundle\Entity\Score;
use Cssr\MainBundle\Form\ScoreType;
use Cssr\MainBundle\Model\Report;

/**
 * Score controller.
 *
 * @Route("/score")
 */
class ScoreController extends Controller
{

    /**
     * Lists all Score entities.
     *
     * @Route("/", name="score")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * Lists all Score entities.
     *
     * @Route("/overall", name="score_overall")
     * @Method("GET")
     * @Template()
     */
    public function overallAction()
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

        $reports = Report::getFridayAll($em,$activeCenter,$areas,$period);

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

    /**
     * Lists student entities.
     *
     * @Route("/student", name="score_student")
     * @Method("GET")
     * @Template()
     */
    public function studentIndexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        if ( $center ) {

            $sql = "SELECT U.*
            FROM cssr_user_group UG
            LEFT JOIN cssr_user U ON U.id = UG.user_id
            WHERE U.center_id = :centerId AND UG.group_id = :groupId
            ORDER BY U.lastname, U.firstname ";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('centerId', $center->id);
            $stmt->bindValue('groupId', 6);

            $stmt->execute();

            $result = $stmt->fetchAll();

        } else {

            $sql = "SELECT U.*
            FROM cssr_user_group UG
            LEFT JOIN cssr_user U ON U.id = UG.user_id
            WHERE UG.group_id = :groupId
            ORDER BY U.lastname, U.firstname ";


            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('groupId', 6);

            $stmt->execute();

            $result = $stmt->fetchAll();
        }

        return array(
            'entities' => $result
        );
    }

    /**
     * Lists scores for a student.
     *
     * @Route("/student/{id}", name="score_student_show")
     * @Method("GET")
     * @Template()
     */
    public function studentScoreAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $student = $em->getRepository('CssrMainBundle:User')->find($id);

        if ( !$student ) {
            throw $this->createNotFoundException('Unable to find Student entity.');
        }

        $sql = "
        SELECT C.id, A.id area_id, A.name area_name, U.id user_id, U.firstname user_firstname, U.lastname user_lastname
        FROM cssr_student_course UC
        LEFT JOIN cssr_course C ON C.id = UC.course_id
        LEFT JOIN cssr_area A ON A.id = C.area_id
        LEFT JOIN cssr_user U ON U.id = C.user_id
        WHERE UC.student_id = :userId";

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $id);
        $stmt->execute();
        $courses = $stmt->fetchAll();

        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        // calculate current week and last completed week
        $today = new \DateTime();

        if ( $today->format('w') < 6 || ($today->format('w') == 6 && $today->format('H') < 14 ) ) {
            $offset = 0 - $today->format('w');
        } else {
            $offset = 7 - $today->format('w');
        }

        $period_current = new \DateTime('now');
        if ( $offset > 0 ) {
            $period_current->add(new \DateInterval('P'.$offset.'D'));
        } else if ( $offset < 0 )   {
            $period_current->sub(new \DateInterval('P'.abs($offset).'D'));
        }

        $period_last = clone $period_current;
        $offset = $offset - 7;
        $period_last->sub(new \DateInterval('P'.abs($offset).'D'));

        $periods = array(
            $period_last,
            $period_current
        );

        if ( isset($_GET['period']) ) {
            $period = new \DateTime($_GET['period']);
        } else {
            if ( !empty($periods) ) {
                $period = $periods[count($periods)-1];
            } else {
                $period = new \DateTime('now');
            }
        }

        $period_start = clone $period;
        $period_start->sub(new \DateInterval('P1D'));

        $period_end = clone $period;
        $period_end->add(new \DateInterval('P5D'));

        $scores = null;
        if ( $periods ) {
            $sql = "SELECT * FROM cssr_score WHERE student_id = ".$id." AND period = '".$period->format("Y-m-d H:i:s")."'";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $scores = $stmt->fetchAll();
        }

        return array(
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'student' => $student,
            'courses' => $courses,
            'standards' => $standards,
            'scores' => $scores
        );
    }

    /**
     * Lists all Score entities.
     *
     * @Route("/staff", name="score_staff")
     * @Method("GET")
     * @Template()
     */
    public function staffIndexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        if ( $center ) {

            $sql = "SELECT U.*
            FROM cssr_user_group UG
            LEFT JOIN cssr_user U ON U.id = UG.user_id
            WHERE U.center_id = :centerId AND UG.group_id = :groupId
            ORDER BY U.lastname, U.firstname ";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('centerId', $center->id);
            $stmt->bindValue('groupId', 5);

            $stmt->execute();

            $result = $stmt->fetchAll();

        } else {

            $sql = "SELECT U.*
            FROM cssr_user_group UG
            LEFT JOIN cssr_user U ON U.id = UG.user_id
            WHERE UG.group_id = :groupId
            ORDER BY U.lastname, U.firstname ";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('groupId', 5);

            $stmt->execute();

            $result = $stmt->fetchAll();
        }

        return array(
            'entities' => $result
        );
    }

    /**
     * Lists scores for a staffer.
     *
     * @Route("/staff/{id}", name="score_staff_show")
     * @Method("GET")
     * @Template()
     */
    public function staffScoreAction ( $id ) {
        $em = $this->getDoctrine()->getManager();

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if ( !$staff ) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        // calculate current week and last completed week
        $today = new \DateTime();

        if ( $today->format('w') < 6 || ($today->format('w') == 6 && $today->format('H') < 14 ) ) {
            $offset = 0 - $today->format('w');
        } else {
            $offset = 7 - $today->format('w');
        }

        $period_current = new \DateTime('now');
        if ( $offset > 0 ) {
            $period_current->add(new \DateInterval('P'.$offset.'D'));
        } else if ( $offset < 0 )   {
            $period_current->sub(new \DateInterval('P'.abs($offset).'D'));
        }

        $period_last = clone $period_current;
        $offset = $offset - 7;
        $period_last->sub(new \DateInterval('P'.abs($offset).'D'));

        $periods = array(
            $period_last,
            $period_current
        );

        if ( isset($_GET['period']) ) {
            $period = new \DateTime($_GET['period']);
        } else {
            if ( !empty($periods) ) {
                $period = $periods[count($periods)-1];
            } else {
                $period = new \DateTime('now');
            }
        }

        $period_start = clone $period;
        $period_start->sub(new \DateInterval('P1D'));

        $period_end = clone $period;
        $period_end->add(new \DateInterval('P5D'));

        $sql  = 'SELECT C.id, A.name ';
        $sql .= 'FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'WHERE C.user_id = '.$id;
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $courses = $stmt->fetchAll();

        $courseIds = array();
        foreach ( $courses as $c ) {
            $courseIds[] = $c['id'];
        }

        $sql  = 'SELECT S.id, S.firstname, S.lastname ';
        $sql .= 'FROM cssr_student_course SC ';
        $sql .= 'LEFT JOIN cssr_user S ON S.id = SC.student_id ';
        $sql .= 'WHERE SC.course_id IN ('.implode(',',$courseIds).') ';
        $sql .= 'ORDER BY S.firstname';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        $studentIds = array();
        foreach ( $students as $s ) {
            $studentIds[] = $s['id'];
        }

        $sql = "
        SELECT C.id, A.id area_id, A.name area_name, U.id user_id, U.firstname user_firstname, U.lastname user_lastname, U.middlename user_middlename
        FROM cssr_student_course UC
        LEFT JOIN cssr_course C ON C.id = UC.course_id
        LEFT JOIN cssr_area A ON A.id = C.area_id
        LEFT JOIN cssr_user U ON U.id = UC.student_id
        WHERE C.id IN (".implode(',',$courseIds).")
        ORDER BY area_id, U.lastname, U.firstname";

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $id);
        $stmt->execute();
        $student_courses = $stmt->fetchAll();

        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $scores = null;
        if ( $periods ) {
            $sql = "SELECT * FROM cssr_score WHERE course_id in (".implode(',',$courseIds).") AND period = '".$period->format("Y-m-d H:i:s")."'";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $scores = $stmt->fetchAll();
        }

        return array(
            'user' => $this->getUser(),
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'students' => $students,
            'student_courses' => $student_courses,
            'courses' => $courses,
            'standards' => $standards,
            'scores' => $scores,
            'staff' => $staff
        );
    }

    /**
     * Creates a new Score entity.
     *
     * @Route("/", name="score_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Score:new.html.twig")
     */
    public function createAction ( Request $request ) {
        $em = $this->getDoctrine()->getManager();

        $score  = new Score();
        $form = $this->createForm(new ScoreType(), $score);
        $form->submit($request);

        $isValid = true;
        if ( !empty($_POST['value']) ) {
            $value = $_POST['value'];
            if ( $value == 'N/A' ) {
                $value = null;
                $score->setValue($value);
            } else if ( in_array($value,array(1,2,3,4,5)) ) {
                $value = (int) $value;
                $score->setValue($value);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( !empty($_POST['student']) ) {
            $student = $em->getRepository('CssrMainBundle:User')->find($_POST['student']);
            if ( $student ) {
                $score->setStudent($student);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( !empty($_POST['course']) ) {
            $course = $em->getRepository('CssrMainBundle:Course')->find($_POST['course']);
            if ( $course ) {
                $score->setCourse($course);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( !empty($_POST['period']) ) {
            $period = new \DateTime($_POST['period']);
            $score->setPeriod($period);
        } else {
            $isValid = false;
        }

        //{"value":"5","student":"49446","course":"91","period":"2013-10-27"}

        if ( $isValid ) {

            $score->setCreatedBy($this->getUser());
            $score->setUpdatedBy($this->getUser());

            $em->persist($score);
            $em->flush();

            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'success';
                $api_response->scoreId = $score->getId();

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                return $this->redirect($this->generateUrl('score_show', array('id' => $score->getId())));
            }
        }

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';
            $api_response->data = $_POST;


            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return array(
            'entity' => $score,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Score entity.
     *
     * @Route("/new", name="score_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Score();
        $form   = $this->createForm(new ScoreType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Score entity.
     *
     * @Route("/{id}", name="score_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Score')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Score entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Score entity.
     *
     * @Route("/{id}/edit", name="score_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Score')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Score entity.');
        }

        $editForm = $this->createForm(new ScoreType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Score entity.
     *
     * @Route("/{id}", name="score_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Score:edit.html.twig")
     */
    public function updateAction ( Request $request, $id ) {

        $em = $this->getDoctrine()->getManager();

        $score = $em->getRepository('CssrMainBundle:Score')->find($id);

        if (!$score) {
            throw $this->createNotFoundException('Unable to find Score entity.');
        }


        //$deleteForm = $this->createDeleteForm($id);
        //$editForm = $this->createForm(new ScoreType(), $score);
        //$editForm->submit($request);

        $value = $request->request->get('value');

        $isValid = true;
        if ( !empty($value) ) {
            //$value = $_POST['value'];
            if ( $value == 'N/A' ) {
                $value = null;
                $score->setValue($value);
            } else if ( in_array($value,array(1,2,3,4,5)) ) {
                $value = (int) $value;
                $score->setValue($value);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( $isValid ) {

            $em->persist($score);
            $em->flush();

            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'success';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        }

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return array(
            'entity'      => $score,
            //'edit_form'   => $editForm->createView(),
            //'delete_form' => $deleteForm->createView(),
        );

    }
    /**
     * Deletes a Score entity.
     *
     * @Route("/{id}", name="score_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:Score')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Score entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('score'));
    }

    /**
     * Creates a form to delete a Score entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
