<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Cssr\MainBundle\Entity\Center;
use Cssr\MainBundle\Form\CenterType;
use Cssr\MainBundle\Model\Group;


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

        $entities = $em->getRepository('CssrMainBundle:Center')->findBy(
            array('active' => 1),
            array('name' => 'ASC')
        );

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
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity->setCreatedBy($this->getUser());
            $entity->setUpdatedBy($this->getUser());

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Center created successfully!'
            );

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

        $entity->setUpdatedBy($this->getUser());

        // Create an array of the current Dorm objects in the database
        $originalDorms = array();
        foreach ($entity->getDorms() as $dorm) {
            $originalDorms[] = $dorm;
        }

        // Create an array of the current Vocation objects in the database
        $originalVocations = array();
        foreach ($entity->getVocations() as $vocation) {
            $originalVocations[] = $vocation;
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new CenterType(), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {

            // filter $originalDorms to contain Dorms no longer present
            $removedDorms = array();
            foreach ($entity->getDorms() as $dorm) {
                foreach ($originalDorms as $key => $toDel) {
                    if ($toDel->getId() === $dorm->getId()) {
                        $removedDorms[] = $originalDorms[$key];
                    }
                }
            }

            // remove the relationship between the Dorm and the Center
            foreach ($removedDorms as $dorm) {
                $em->remove($dorm);
            }

            // filter $originalVocations to contain Vocations no longer present
            $removedVocations = array();
            foreach ($entity->getVocations() as $vocation) {
                foreach ($originalVocations as $key => $toDel) {
                    if ($toDel->getId() === $vocation->getId()) {
                        $removedVocations[] = $originalVocations[$key];
                    }
                }
            }

            // remove the relationship between the Vocation and the Center
            foreach ($removedVocations as $vocation) {
                $em->remove($vocation);
            }

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Center updated successfully!'
            );

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
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:Center')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Center entity.');
            }

            $entity->setActive(0); // logical delete

            $em->persist($entity);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                $api_response = new \stdClass();
                $api_response->status = 'success';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

        }

        if ($request->isXmlHttpRequest()) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            return $this->redirect($this->generateUrl('center'));
        }
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

    /**
     * Get center list for global menu
     *
     * @Route("/menu", name="center_menu")
     * @Method("GET")
     * @Template("CssrMainBundle:Center:menu.html.twig")
     */
    public function showMenuAction()
    {
        $em = $this->getDoctrine()->getManager();
        $centers = $em->getRepository('CssrMainBundle:Center')->findBy(
            array('active' => 1),
            array('name' => 'ASC')
        );

        $session = $this->getRequest()->getSession();
        $current = $session->get('center');
        if (!$current) {
            $current = new \stdClass();
            $current->id = null;
            $current->name = 'All Centers';
        }

        return array(
            'user' => $this->getUser(),
            'current_center' => $current,
            'centers' => $centers
        );
    }

    /**
     * Set active center ajax call
     *
     * @Route("/{id}/activate", name="center_activate")
     * @Method("POST")
     */
    public function setActiveAction ( $id ) {
        if ( !empty($id) ) {
            $em = $this->getDoctrine()->getManager();
            $center = $em->getRepository('CssrMainBundle:Center')->find($id);

            if (!$center) {
                throw $this->createNotFoundException('Unable to find Center entity.');
            }

            $sess_center = new \stdClass();
            $sess_center->id = $center->getId();
            $sess_center->name = $center->getName();

        } else {
            $sess_center = new \stdClass();
            $sess_center->id = null;
            $sess_center->name = 'All Centers';
        }

        $session = $this->getRequest()->getSession();
        $session->set('center', $sess_center);

        $api_response = new \stdClass();
        $api_response->status = 'success';

        // create a JSON-response with a 200 status code
        $response = new Response(json_encode($api_response));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
