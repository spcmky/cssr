<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\Score;
use Cssr\MainBundle\Form\ScoreType;

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
        $em = $this->getDoctrine()->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $scores = array();
        for ( $i = 0; $i < 25; $i++ ) {
            $scores[$i] = array();

            $scores[$i][0] = uniqid().', '.uniqid();

            $total = 0;
            $units = 0;
            for ( $j = 1; $j < 20; $j++ ) {
                if ( rand(1,5) == 1 ) {
                    $total += $scores[$i][$j] = rand(0,5);
                    $units++;
                } else {
                    $scores[$i][$j] = null;
                }
            }

            $scores[$i][23] = $total; // total units
            $scores[$i][24] = (!$units)? 0 : round(($total/$units),1); // average
            $scores[$i][25] = 'Gold'; // status
        }

        return array(
            'areas' => $areas,
            'standards' => $standards,
            'scores' => $scores
        );
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

        $entities = $em->getRepository('CssrMainBundle:Group')->findByName('Student');

        return array(
            'entities' => $entities[0]->getUsers()
        );
    }

    /**
     * Lists scores for a student.
     *
     * @Route("/student/{id}", name="score_student_show")
     * @Method("GET")
     * @Template()
     */
    public function studentScoreAction()
    {
        $em = $this->getDoctrine()->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        return array(
            'areas' => $areas,
            'standards' => $standards
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

        $entities = $em->getRepository('CssrMainBundle:Group')->findByName('Staff');

        return array(
            'entities' => $entities[0]->getUsers()
        );
    }

    /**
     * Lists scores for a staffer.
     *
     * @Route("/staff/{id}", name="score_staff_show")
     * @Method("GET")
     * @Template()
     */
    public function staffScoreAction()
    {
        return $this->indexAction();
    }

    /**
     * Creates a new Score entity.
     *
     * @Route("/", name="score_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Score:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new Score();
        $form = $this->createForm(new ScoreType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('score_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
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
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Score')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Score entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new ScoreType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('score_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
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
