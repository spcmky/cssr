<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\User;
use Cssr\MainBundle\Form\StaffType;

/**
 * Staff controller.
 *
 * @Route("/staff")
 */
class StaffController extends Controller
{

    /**
     * Lists all Staff entities.
     *
     * @Route("/", name="staff")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        if ( $center ) {

            $sql = "SELECT U.* FROM cssr_user_group UG LEFT JOIN cssr_user U ON U.id = UG.user_id WHERE U.center_id = :centerId AND UG.group_id = :groupId";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('centerId', $center->id);
            $stmt->bindValue('groupId', 5);

            $stmt->execute();

            $result = $stmt->fetchAll();

        } else {

            $sql = "SELECT U.* FROM cssr_user_group UG LEFT JOIN cssr_user U ON U.id = UG.user_id WHERE UG.group_id = :groupId";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('groupId', 5);

            $stmt->execute();

            $result = $stmt->fetchAll();
        }


        return array(
            'entities' => $result
        );
    }

    /**
     * Creates a new Staff entity.
     *
     * @Route("/", name="staff_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Staff:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new User();
        $form = $this->createForm(new StaffType(), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('staff_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Staff entity.
     *
     * @Route("/new", name="staff_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new User();
        $form = $this->createForm(new StaffType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Staff entity.
     *
     * @Route("/{id}", name="staff_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $sql  = 'SELECT C.id, A.name ';
        $sql .= 'FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'WHERE C.user_id = '.$id;
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $courses = $stmt->fetchAll();

        $courseIds = array();
        foreach ( $courses as $c ) {
            $courseIds[] = $c['id'];
        }

        $sql  = 'SELECT S.firstname, S.lastname ';
        $sql .= 'FROM cssr_student_course SC ';
        $sql .= 'LEFT JOIN cssr_user S ON S.id = SC.student_id ';
        $sql .= 'WHERE SC.course_id IN ('.implode(',',$courseIds).') ';
        $sql .= 'ORDER BY S.firstname';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
            'courses' => $courses,
            'students' => $students
        );
    }

    /**
     * Displays a form to edit an existing Staff entity.
     *
     * @Route("/{id}/edit", name="staff_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $editForm = $this->createForm(new StaffType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Staff entity.
     *
     * @Route("/{id}", name="staff_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Staff:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new StaffType(), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('staff_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Staff entity.
     *
     * @Route("/{id}", name="staff_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:User')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Staff entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('staff'));
    }

    /**
     * Creates a form to delete a Staff entity by id.
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
