<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\Center;
use Cssr\MainBundle\Form\CenterType;

/**
 * Center controller.
 *
 * @Route("/center")
 */
class CenterController extends Controller
{

    /**
     * Lists all Center entities.
     *
     * @Route("/", name="center")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('CssrMainBundle:Center')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Center entity.
     *
     * @Route("/", name="center_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Center:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new Center();
        $form = $this->createForm(new CenterType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('center_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Center entity.
     *
     * @Route("/new", name="center_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Center();
        $form   = $this->createForm(new CenterType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Center entity.
     *
     * @Route("/{id}", name="center_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Center')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Center entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Center entity.
     *
     * @Route("/{id}/edit", name="center_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Center')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Center entity.');
        }

        $editForm = $this->createForm(new CenterType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Center entity.
     *
     * @Route("/{id}", name="center_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Center:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Center')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Center entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new CenterType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('center_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Center entity.
     *
     * @Route("/{id}", name="center_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:Center')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Center entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('center'));
    }

    /**
     * Creates a form to delete a Center entity by id.
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
