<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\Vocation;
use Cssr\MainBundle\Form\VocationType;

/**
 * Vocation controller.
 *
 * @Route("/vocation")
 */
class VocationController extends Controller
{

    /**
     * Lists all Vocation entities.
     *
     * @Route("/", name="vocation")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('CssrMainBundle:Vocation')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Vocation entity.
     *
     * @Route("/", name="vocation_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Vocation:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new Vocation();
        $form = $this->createForm(new VocationType(), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('vocation_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Vocation entity.
     *
     * @Route("/new", name="vocation_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Vocation();
        $form   = $this->createForm(new VocationType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Vocation entity.
     *
     * @Route("/{id}", name="vocation_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Vocation')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vocation entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Vocation entity.
     *
     * @Route("/{id}/edit", name="vocation_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Vocation')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vocation entity.');
        }

        $editForm = $this->createForm(new VocationType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Vocation entity.
     *
     * @Route("/{id}", name="vocation_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Vocation:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Vocation')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vocation entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new VocationType(), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {

            $em->flush();

            return $this->redirect($this->generateUrl('vocation_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Vocation entity.
     *
     * @Route("/{id}", name="vocation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:Vocation')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Vocation entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('vocation'));
    }

    /**
     * Creates a form to delete a Vocation entity by id.
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
