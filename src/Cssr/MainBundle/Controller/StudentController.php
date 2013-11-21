<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Cssr\MainBundle\Entity\User;
use Cssr\MainBundle\Form\StudentType;
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

            $sql = "SELECT U.*
            FROM cssr_user_group UG
            LEFT JOIN cssr_user U ON U.id = UG.user_id
            WHERE U.center_id = :centerId AND UG.group_id = :groupId
            ORDER BY U.lastname, U.firstname";

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
            ORDER BY U.lastname, U.firstname";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('groupId', 6);

            $stmt->execute();

            $result = $stmt->fetchAll();
        }


        return array(
            'students' => $result
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

        $form = $this->createForm(new StudentType(array(
            'studentCourses' => array(),
            'centerCourses' => $centerCourses,
            'center' => $center,
            'dorms' => $em->getRepository('CssrMainBundle:Dorm')->findByCenter($activeCenter->id),
            'centers' => $em->getRepository('CssrMainBundle:Center')->findAll()
        )), $student);

        $form->submit($request);

        if ( $form->isValid() ) {

            $student->setEnabled(true);
            $student->addGroup($em->getRepository('CssrMainBundle:Group')->find(6)) ;

            $em->persist($student);
            $em->flush();

            // take care of courses
            $data = $request->request->get('cssr_mainbundle_studenttype');
            Student::enroll($em,$student,$data['enrollment']);

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

        $form = $this->createForm(new StudentType(array(
            'studentCourses' => array(),
            'centerCourses' => $centerCourses,
            'center' => $center,
            'dorms' => $em->getRepository('CssrMainBundle:Dorm')->findByCenter($activeCenter->id),
            'centers' => $em->getRepository('CssrMainBundle:Center')->findAll()
        )), $student);

        return array(
            'student' => $student,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Student entity.
     *
     * @Route("/{id}", name="student_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:User')->find($id);

        if ( !$entity ) {
            throw $this->createNotFoundException('Unable to find Student entity.');
        }

        $sql = "
        SELECT A.id area_id, A.name area_name, U.id user_id, U.firstname user_firstname, U.lastname user_lastname
        FROM cssr_student_course UC
        LEFT JOIN cssr_course C ON C.id = UC.course_id
        LEFT JOIN cssr_area A ON A.id = C.area_id
        LEFT JOIN cssr_user U ON U.id = C.user_id
        WHERE UC.student_id = :userId";

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $id);
        $stmt->execute();

        $courses = $stmt->fetchAll();

        return array(
            'entity' => $entity,
            'courses' => $courses
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

        $editForm = $this->createForm(new StudentType(array(
            'studentCourses' => $studentCourses,
            'centerCourses' => $centerCourses,
            'center' => $center,
            'dorms' => $em->getRepository('CssrMainBundle:Dorm')->findByCenter($activeCenter->id),
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

        $editForm = $this->createForm(new StudentType(array(
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
            $data = $request->request->get('cssr_mainbundle_studenttype');
            Student::enroll($em,$student,$data['enrollment']);

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
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:User')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Student entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('student'));
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
