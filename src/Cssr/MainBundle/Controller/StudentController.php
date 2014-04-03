<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Doctrine\ORM\EntityNotFoundException;

use Cssr\MainBundle\Entity\User;
use Cssr\MainBundle\Form\StudentCreateType;
use Cssr\MainBundle\Form\StudentUpdateType;
use Cssr\MainBundle\Model\Center;
use Cssr\MainBundle\Model\Student;



/**
 * Student controller.
 *
 * @Route("/student")
 */
class StudentController extends Controller
{

    /**
     * Lists all Student entities.
     *
     * @Route("/", name="student")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        if ( $center ) {

            $sql  = "SELECT U.* ";
            $sql .= "FROM cssr_user_group UG ";
            $sql .= "LEFT JOIN cssr_user U ON U.id = UG.user_id ";
            $sql .= "WHERE U.enabled = :enabled AND U.center_id = :centerId AND UG.group_id = :groupId ";
            $sql .= "ORDER BY U.lastname, U.firstname, U.middlename ";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('enabled', 1,\PDO::PARAM_INT);
            $stmt->bindValue('centerId', $center->id,\PDO::PARAM_INT);
            $stmt->bindValue('groupId', 6, \PDO::PARAM_INT);

            $stmt->execute();

            $result = $stmt->fetchAll();

        } else {

            $sql  = "SELECT U.* ";
            $sql .= "FROM cssr_user_group UG ";
            $sql .= "LEFT JOIN cssr_user U ON U.id = UG.user_id ";
            $sql .= "WHERE U.enabled = :enabled AND UG.group_id = :groupId ";
            $sql .= "ORDER BY U.lastname, U.firstname, U.middlename ";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('enabled', 1,\PDO::PARAM_INT);
            $stmt->bindValue('groupId', 6, \PDO::PARAM_INT);

            $stmt->execute();

            $result = $stmt->fetchAll();
        }

        return array(
            'students' => $result
        );
    }

    /**
     * Displays a form to create a new Student entity.
     *
     * @Route("/new", name="student_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $em = $this->getDoctrine()->getManager();

        $student = new User();

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $centerCourses = Center::getCourses($em,$center);

        $form = $this->createForm(new StudentCreateType(array(
            'date' => new \DateTime(),
            'studentCourses' => array(),
            'centerCourses' => $centerCourses,
            'center' => $center,
            'dorms' => $em->getRepository('CssrMainBundle:Dorm')->findByCenter($activeCenter->id)
        )), $student);

        return array(
            'student' => $student,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new Student entity.
     *
     * @Route("/", name="student_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Student:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $student = new User();

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $centerCourses = Center::getCourses($em,$center);

        $form = $this->createForm(new StudentCreateType(array(
            'date' => new \DateTime(),
            'studentCourses' => array(),
            'centerCourses' => $centerCourses,
            'center' => $center,
            'dorms' => $em->getRepository('CssrMainBundle:Dorm')->findByCenter($activeCenter->id)
        )), $student);

        $form->submit($request);

        if ( $form->isValid() ) {

            $student->setEnabled(true);
            $student->addGroup($em->getRepository('CssrMainBundle:Group')->find(6));
            $student->setCenter($center);

            $em->persist($student);
            $em->flush();

            $em->flush();

            // take care of courses
            $data = $request->request->get('cssr_mainbundle_student_create_type');

            if ( isset($data['courses']) ) {
                $courseList = array();
                foreach ( $data['courses'] as $cid ) {
                    if ( !empty($cid) ) {
                        $courseList[] = (int) $cid;
                    }
                }
                Student::enroll($em,$student,$courseList);
            }

            $this->get('session')->getFlashBag()->add(
                'success',
                'Student created successfully!'
            );

            return $this->redirect($this->generateUrl('student_show', array('id' => $student->getId())));
        }

        return array(
            'student' => $student,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Student entity.
     *
     * @Route("/{id}/edit", name="student_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $student = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Unable to find Student.');
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);


        $studentCourses = Student::getCourses($em,$student);
        $centerCourses = Center::getCourses($em,$center);

        $editForm = $this->createForm(new StudentUpdateType(array(
            'date' => $student->getEntry(),
            'studentCourses' => $studentCourses,
            'centerCourses' => $centerCourses,
            'center' => $center,
            'dorms' => $em->getRepository('CssrMainBundle:Dorm')->findByCenter($center->getId()),
            'centers' => $em->getRepository('CssrMainBundle:Center')->findAll()
        )), $student);

        return array(
            'student' => $student,
            'edit_form' => $editForm->createView()
        );
    }

    /**
     * Edits an existing Student entity.
     *
     * @Route("/{id}", name="student_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Student:edit.html.twig")
     */
    public function updateAction ( Request $request, $id )
    {
        $em = $this->getDoctrine()->getManager();

        $student = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Unable to find Student.');
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $studentCourses = Student::getCourses($em,$student);
        $centerCourses = Center::getCourses($em,$center);

        $editForm = $this->createForm(new StudentUpdateType(array(
            'date' => $student->getEntry(),
            'studentCourses' => $studentCourses,
            'centerCourses' => $centerCourses,
            'center' => $center,
            'dorms' => $em->getRepository('CssrMainBundle:Dorm')->findByCenter($activeCenter->id),
            'centers' => $em->getRepository('CssrMainBundle:Center')->findAll()
        )), $student);

        $editForm->submit($request);

        if ( $editForm->isValid() ) {

            $em->flush();

            // take care of courses
            $data = $request->request->get('cssr_mainbundle_student_update_type');

            if ( isset($data['courses']) ) {
                $courseList = array();
                foreach ( $data['courses'] as $cid ) {
                    if ( !empty($cid) ) {
                        $courseList[] = (int) $cid;
                    }
                }
                Student::enroll($em,$student,$courseList);
            }

            $this->get('session')->getFlashBag()->add(
                'success',
                'Student updated successfully!'
            );

            return $this->redirect($this->generateUrl('student_edit', array('id' => $id)));
        }

        return array(
            'student' => $student,
            'edit_form'   => $editForm->createView()
        );
    }

    /**
     * Deletes a Student entity.
     *
     * @Route("/{id}", name="student_delete")
     * @Method("DELETE")
     */
    public function deleteAction ( Request $request, $id )
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('CssrMainBundle:User')->find($id);

        if ( !$user ) {
            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'failed';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                throw $this->createNotFoundException('Unable to find Student.');
            }
        }

        Student::unEnroll($em,$user);
        $user->setEnabled(0); // logical delete

        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',
            'Student deleted successfully!'
        );

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'success';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            return $this->redirect($this->generateUrl('student'));
        }
    }

    /**
     * Finds and displays a Student entity.
     *
     * @Route("/{id}", name="student_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction ( $id )
    {
        $em = $this->getDoctrine()->getManager();

        $student = $em->getRepository('CssrMainBundle:User')->find($id);

        if ( !$student ) {
            throw $this->createNotFoundException('Unable to find Student.');
        }

        $dormName = null;
        try {
            $dorm = $student->getDorm();

            if ( $dorm ) {
                $dormName = $dorm->getName();
            }
        } catch ( EntityNotFoundException $e ) {
            $dormName = null;
        }

        $sql  = 'SELECT A.id area_id, A.name area_name, U.id user_id, U.firstname user_firstname, U.lastname user_lastname ';
        $sql .= 'FROM cssr_student_course SC ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = SC.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = C.user_id ';
        $sql .= 'WHERE SC.student_id = :userId AND SC.enrolled = :enrolled AND C.active = :active ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $id);
        $stmt->bindValue('enrolled', 1, \PDO::PARAM_INT);
        $stmt->bindValue('active', 1, \PDO::PARAM_INT);
        $stmt->execute();

        $courses = $stmt->fetchAll();

        return array(
            'student' => $student,
            'dorm' => $dormName,
            'courses' => $courses
        );
    }

    /**
     * Creates a form to delete a Student entity by id.
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
