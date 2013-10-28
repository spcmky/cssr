<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\Dorm;
use Cssr\MainBundle\Form\DormType;

/**
 * Dorm controller.
 *
 * @Route("/dorm")
 */
class DormController extends Controller
{

    /**
     * Lists all Dorm entities.
     *
     * @Route("/", name="dorm")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('CssrMainBundle:Dorm')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Dorm entity.
     *
     * @Route("/", name="dorm_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Dorm:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new Dorm();
        $form = $this->createForm(new DormType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('dorm_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Dorm entity.
     *
     * @Route("/new", name="dorm_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Dorm();
        $form   = $this->createForm(new DormType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Dorm entity.
     *
     * @Route("/{id}", name="dorm_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Dorm')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Dorm entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Dorm entity.
     *
     * @Route("/{id}/edit", name="dorm_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Dorm')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Dorm entity.');
        }

        $editForm = $this->createForm(new DormType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Dorm entity.
     *
     * @Route("/{id}", name="dorm_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Dorm:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Dorm')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Dorm entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new DormType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            $em->flush();

            return $this->redirect($this->generateUrl('dorm_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Dorm entity.
     *
     * @Route("/{id}", name="dorm_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:Dorm')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Dorm entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('dorm'));
    }

    /**
     * Creates a form to delete a Dorm entity by id.
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
