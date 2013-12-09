<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\User;
use Cssr\MainBundle\Form\StaffType;
use Cssr\MainBundle\Model\Staff;

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

            $sql = "SELECT U.*
            FROM cssr_user_group UG
            LEFT JOIN cssr_user U ON U.id = UG.user_id
            WHERE U.center_id = :centerId AND UG.group_id < :groupId
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
            WHERE UG.group_id < :groupId
            ORDER BY U.lastname, U.firstname";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('groupId', 6);

            $stmt->execute();

            $result = $stmt->fetchAll();
        }


        return array(
            'entities' => $result
        );
    }

    /**
     * Lists all Staff entities.
     *
     * @Route("/group/{group_id}", name="staff_group")
     * @Method("GET")
     * @Template()
     */
    public function groupIndexAction ( $group_id )
    {
        $em = $this->getDoctrine()->getManager();

        $group = $em->getRepository('CssrMainBundle:Group')->find($group_id);

        if (!$group) {
            throw $this->createNotFoundException('Unable to find staff group.');
        }

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        if ( $center && $group_id > 1 ) {

            $sql = "SELECT U.*
            FROM cssr_user_group UG
            LEFT JOIN cssr_user U ON U.id = UG.user_id
            WHERE U.center_id = :centerId AND UG.group_id = :groupId
            ORDER BY U.lastname, U.firstname";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('centerId', $center->id);
            $stmt->bindValue('groupId', $group_id);

            $stmt->execute();

            $result = $stmt->fetchAll();

        } else {

            $sql = "SELECT U.*
            FROM cssr_user_group UG
            LEFT JOIN cssr_user U ON U.id = UG.user_id
            WHERE UG.group_id = :groupId
            ORDER BY U.lastname, U.firstname";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('groupId', $group_id);

            $stmt->execute();

            $result = $stmt->fetchAll();
        }


        return array(
            'group' => $group,
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
    public function createAction ( Request $request )
    {
        $params = $request->request->get('cssr_mainbundle_stafftype');

        $em = $this->getDoctrine()->getManager();

        $groupId = array_pop($params['groups']);
        $group = $em->getRepository('CssrMainBundle:Group')->find($groupId);

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);
        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();

        $staff = new User();

        $form = $this->createForm(new StaffType(array(
            'center' => $center,
            'group' => $group,
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centerCourses' => $areas,
            'staffCourses' => Staff::getCourses($em,$staff)
        )), $staff);

        $form->submit($request);

        if ( $form->isValid() ) {

            $staff->setEnabled(true) ;
            $staff->addGroup($group);

            $em->persist($staff);
            $em->flush();

            $data = $request->request->get('cssr_mainbundle_stafftype');
            Staff::updateCourses($em,$staff,array($data['area']));

            $this->get('session')->getFlashBag()->add(
                'success',
                'User created successfully!'
            );

            return $this->redirect($this->generateUrl('staff_show', array('id' => $staff->getId())));
        }

        return array(
            'entity' => $staff,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Staff entity.
     *
     * @Route("/new/{groupId}", name="staff_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction ( $groupId )
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $staff = new User();
        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();


        $form = $this->createForm(new StaffType(array(
            'center' => $center,
            'group' => $em->getRepository('CssrMainBundle:Group')->find($groupId),
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centerCourses' => $areas,
            'staffCourses' => Staff::getCourses($em,$staff)
        )), $staff);


        return array(
            'staff' => $staff,
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

        $students = null;
        if ( !empty($courseIds) ) {
            $sql  = 'SELECT S.firstname, S.lastname ';
            $sql .= 'FROM cssr_student_course SC ';
            $sql .= 'LEFT JOIN cssr_user S ON S.id = SC.student_id ';
            $sql .= 'WHERE SC.course_id IN ('.implode(',',$courseIds).') ';
            $sql .= 'ORDER BY S.firstname';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $students = $stmt->fetchAll();
        }

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
    public function editAction ( $id )
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Unable to find user.');
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();

        $editForm = $this->createForm(new StaffType(array(
            'center' => $center,
            'group' => $user->getGroups()->first(),
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centerCourses' => $areas,
            'staffCourses' => Staff::getCourses($em,$user)
        )), $user);

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'user'      => $user,
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

        $user = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Unable to find user.');
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();

        $deleteForm = $this->createDeleteForm($id);

        $editForm = $this->createForm(new StaffType(array(
            'center' => $center,
            'group' => $user->getGroups()->first(),
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centerCourses' => $areas,
            'staffCourses' => Staff::getCourses($em,$user)
        )), $user);

        $editForm->submit($request);

        if ( $editForm->isValid() ) {


            $em->flush();

            $data = $request->request->get('cssr_mainbundle_stafftype');
            Staff::updateCourses($em,$user,array($data['area']));

            return $this->redirect($this->generateUrl('staff_edit', array('id' => $id)));
        }

        return array(
            'user'      => $user,
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
