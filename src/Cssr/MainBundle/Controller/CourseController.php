<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Cssr\MainBundle\Entity\Course;
use Cssr\MainBundle\Form\CourseType;
use Cssr\MainBundle\Model\Student;

/**
 * Course controller.
 *
 * @Route("/course")
 */
class CourseController extends Controller
{

    /**
     * Lists all Course entities.
     *
     * @Route("/", name="course")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        $sql  = 'SELECT U.*, A.name area_name ';
        $sql .= 'FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = C.user_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';

        if ( $center ) {
            $sql .= 'WHERE U.center_id = :centerId ';
            $sql .= 'ORDER BY A.id ';

            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('centerId', $center->id);
        } else {
            $sql .= 'ORDER BY A.id ';
            $stmt = $em->getConnection()->prepare($sql);
        }

        $stmt->execute();
        $result = $stmt->fetchAll();

        return array(
            'entities' => $result,
        );
    }
    /**
     * Creates a new Course entity.
     *
     * @Route("/", name="course_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Course:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new Course();
        $form = $this->createForm(new CourseType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('course_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Course entity.
     *
     * @Route("/new", name="course_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Course();
        $form   = $this->createForm(new CourseType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Course entity.
     *
     * @Route("/{id}", name="course_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Course entity.
     *
     * @Route("/{id}/edit", name="course_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $editForm = $this->createForm(new CourseType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Course entity.
     *
     * @Route("/{id}", name="course_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Course:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new CourseType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('course_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Course entity.
     *
     * @Route("/{id}", name="course_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:Course')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Course entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('course'));
    }

    /**
     * Creates a form to delete a Course entity by id.
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

    /**
     * Enroll student in a course ajax call
     *
     * @Route("/{id}/enroll", name="course_enroll")
     * @Method("POST")
     */
    public function enrollAction ( $id ) {
        $em = $this->getDoctrine()->getManager();

        $course = $em->getRepository('CssrMainBundle:Course')->find($id);
        if ( !$course ) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $student = null;
        if ( !empty($_POST['student']) ) {
            $student = $em->getRepository('CssrMainBundle:User')->find($_POST['student']);
        }

        if ( !$student ) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        Student::enroll($em,$student->getId(),$course->getId());

        $api_response = new \stdClass();
        $api_response->status = 'success';

        // create a JSON-response with a 200 status code
        $response = new Response(json_encode($api_response));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
