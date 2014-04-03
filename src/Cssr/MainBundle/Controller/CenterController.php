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
     * Lists all Centers.
     *
     * @Route("/", name="center")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        if ( Group::isGranted($this->getUser(),'center create') ) {
            $centers = $em->getRepository('CssrMainBundle:Center')->findBy(
                array('active' => 1),
                array('name' => 'ASC')
            );
        } else {
            return $this->redirect($this->generateUrl('cssr_main_default_index'));
        }

        return array(
            'centers' => $centers,
        );
    }
    /**
     * Creates a new Center.
     *
     * @Route("/", name="center_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Center:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $center = new Center();
        $form = $this->createForm(new CenterType(), $center);
        $form->submit($request);

        if ( $form->isValid() ) {
            $em = $this->getDoctrine()->getManager();

            $center->setCreatedBy($this->getUser());
            $center->setUpdatedBy($this->getUser());

            $em->persist($center);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Center created successfully!'
            );

            return $this->redirect($this->generateUrl('center_show', array('id' => $center->getId())));
        }

        return array(
            'center' => $center,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Center.
     *
     * @Route("/new", name="center_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $center = new Center();
        $form   = $this->createForm(new CenterType(), $center);

        return array(
            'center' => $center,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Center.
     *
     * @Route("/{id}", name="center_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $center = $em->getRepository('CssrMainBundle:Center')->find($id);

        if ( !$center ) {
            throw $this->createNotFoundException('Unable to find Center.');
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        if ( $activeCenter ) {
            $activeCenterId = $activeCenter->id;
        } else {
            $activeCenterId = null;
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'user' => $this->getUser(),
            'activeCenterId' => $activeCenterId,
            'center' => $center,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Center.
     *
     * @Route("/{id}/edit", name="center_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $center = $em->getRepository('CssrMainBundle:Center')->find($id);

        if (!$center) {
            throw $this->createNotFoundException('Unable to find Center.');
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        if ( $activeCenter ) {
            $activeCenterId = $activeCenter->id;
        } else {
            $activeCenterId = null;
        }

        $editForm = $this->createForm(new CenterType(), $center);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'user' => $this->getUser(),
            'activeCenterId' => $activeCenterId,
            'center'      => $center,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Center.
     *
     * @Route("/{id}", name="center_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Center:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $center = $em->getRepository('CssrMainBundle:Center')->find($id);

        if (!$center) {
            throw $this->createNotFoundException('Unable to find Center.');
        }

        // Create an array of the current Dorm objects in the database
        $originalDorms = array();
        foreach ($center->getDorms() as $dorm) {
            $originalDorms[] = $dorm->getId();
        }

        // Create an array of the current Vocation objects in the database
        $originalVocations = array();
        foreach ($center->getVocations() as $vocation) {
            $originalVocations[] = $vocation->getId();
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new CenterType(), $center);
        $editForm->submit($request);

        if ( $editForm->isValid() ) {

            $center->setUpdatedBy($this->getUser());

            // filter $originalDorms to contain Dorms no longer present
            $newDorms = array();
            foreach ( $center->getDorms() as $dorm ) {
                $newDorms[] = $dorm->getId();
            }

            // remove the relationship between the Dorm and the Center
            foreach ( array_diff($originalDorms,$newDorms) as $dormId ) {
                $dorm = $em->getRepository('CssrMainBundle:Dorm')->find($dormId);
                $em->remove($dorm);

                // remove relationship between Dorm and users
                $sql = 'UPDATE cssr_user SET dorm_id = NULL WHERE dorm_id = :dormId ';
                $stmt = $em->getConnection()->prepare($sql);
                $stmt->bindValue('dormId', $dormId);
                $stmt->execute();
            }

            // filter $originalVocations to contain Vocations no longer present
            $newVocations = array();
            foreach ( $center->getVocations() as $vocation ) {
                $newVocations[] = $vocation->getId();
            }

            // remove the relationship between the Vocation and the Center
            foreach ( array_diff($originalVocations,$newVocations) as $vocationId ) {
                $vocation = $em->getRepository('CssrMainBundle:Vocation')->find($vocationId);
                $em->remove($vocation);
            }

            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Center updated successfully!'
            );

            return $this->redirect($this->generateUrl('center_edit', array('id' => $id)));
        }

        return array(
            'user' => $this->getUser(),
            'center'      => $center,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Center.
     *
     * @Route("/{id}", name="center_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $center = $em->getRepository('CssrMainBundle:Center')->find($id);

        if ( !$center ) {
            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'failed';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                throw $this->createNotFoundException('Unable to find Center.');
            }
        }

        $center->setActive(0); // logical delete
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',
            'Center deleted successfully!'
        );

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'success';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            return $this->redirect($this->generateUrl('center'));
        }
    }

    /**
     * Creates a form to delete a Center by id.
     *
     * @param mixed $id The center id
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
            $current->name = 'Select Center';
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
                $sess_center = new \stdClass();
                $sess_center->id = -1;
                $sess_center->name = 'Select Center';
            } else {
                $sess_center = new \stdClass();
                $sess_center->id = $center->getId();
                $sess_center->name = $center->getName();
            }

        } else {
            $sess_center = new \stdClass();
            $sess_center->id = -1;
            $sess_center->name = 'Select Center';
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
