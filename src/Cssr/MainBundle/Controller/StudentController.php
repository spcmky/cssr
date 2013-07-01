<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\Student;
use Cssr\MainBundle\Form\StudentType;

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

        $entities = $em->getRepository('CssrMainBundle:Student')->findAll();

        return array(
            'entities' => $entities,
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
        $entity  = new Student();
        $form = $this->createForm(new StudentType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('student_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
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
        $entity = new Student();
        $form   = $this->createForm(new StudentType(), $entity);

        return array(
            'entity' => $entity,
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

        $entity = $em->getRepository('CssrMainBundle:Student')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Student entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
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

        $entity = $em->getRepository('CssrMainBundle:Student')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Student entity.');
        }

        $editForm = $this->createForm(new StudentType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Student entity.
     *
     * @Route("/{id}", name="student_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Student:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Student')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Student entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new StudentType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('student_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
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
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:Student')->find($id);

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
