<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Cssr\MainBundle\Entity\User;
use Cssr\MainBundle\Form\StaffCreateType;
use Cssr\MainBundle\Form\StaffUpdateType;
use Cssr\MainBundle\Model\Staff;


/**
 * Staff controller.
 *
 * @Route("/staff")
 */
class StaffController extends Controller {

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

            $sql  = "SELECT U.* ";
            $sql .= "FROM cssr_user_group UG ";
            $sql .= "LEFT JOIN cssr_user U ON U.id = UG.user_id ";
            $sql .= "WHERE U.enabled = :enabled AND U.center_id = :centerId AND UG.group_id = :groupId ";
            $sql .= "ORDER BY U.lastname, U.firstname, U.middlename ";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('enabled', 1,\PDO::PARAM_INT);
            $stmt->bindValue('centerId', $center->id,\PDO::PARAM_INT);
            $stmt->bindValue('groupId', 5, \PDO::PARAM_INT);

            $stmt->execute();

            $result = $stmt->fetchAll();

        } else {

            $sql  = "SELECT U.* ";
            $sql .= "FROM cssr_user_group UG ";
            $sql .= "LEFT JOIN cssr_user U ON U.id = UG.user_id ";
            $sql .= "WHERE U.enabled = :enabled AND UG.group_id = :groupId ";
            $sql .= "ORDER BY U.lastname, U.firstname, U.middlename ";

            $stmt = $em->getConnection()->prepare($sql);

            $stmt->bindValue('enabled', 1,\PDO::PARAM_INT);
            $stmt->bindValue('groupId', 5, \PDO::PARAM_INT);

            $stmt->execute();

            $result = $stmt->fetchAll();
        }


        return array(
            'staffers' => $result
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
     * Displays a form to create a new Staff entity.
     *
     * @Route("/new", name="staff_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction ()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $group = $em->getRepository('CssrMainBundle:Group')->find(5);
        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();

        $userManager = $this->container->get('fos_user.user_manager');
        $staff = $userManager->createUser();

        $form = $this->createForm(new StaffCreateType(array(
            'group' => $group,
            'centerCourses' => $areas,
            'staffCourses' => Staff::getCourses($em,$staff)
        )), $staff);


        return array(
            'areas' => $areas,
            'center' => $center,
            'group' => $group,
            'staff' => $staff,
            'form' => $form->createView()
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
        $em = $this->getDoctrine()->getManager();

        $group = $em->getRepository('CssrMainBundle:Group')->find(5);
        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);
        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();

        $userManager = $this->container->get('fos_user.user_manager');
        $staff = $userManager->createUser();

        $form = $this->createForm(new StaffCreateType(array(
            'center' => $center,
            'group' => $group,
            'centerCourses' => $areas,
            'staffCourses' => Staff::getCourses($em,$staff)
        )), $staff);

        $form->submit($request);

        if ( $form->isValid() ) {

            $staff->setCenter($center);
            $staff->setEnabled(true);
            $staff->addGroup($group);

            $userManager->updateUser($staff);

            $data = $request->request->get('cssr_mainbundle_staff_create_type');
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
     * Displays a form to edit an existing Staff entity.
     *
     * @Route("/{id}/edit", name="staff_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction ( $id )
    {
        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id'=>$id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find user.');
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();

        $editForm = $this->createForm(new StaffUpdateType(array(
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

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id'=>$id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find user.');
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();

        $deleteForm = $this->createDeleteForm($id);

        $editForm = $this->createForm(new StaffUpdateType(array(
            'center' => $center,
            'group' => $user->getGroups()->first(),
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centerCourses' => $areas,
            'staffCourses' => Staff::getCourses($em,$user)
        )), $user);

        $editForm->submit($request);

        if ( $editForm->isValid() ) {

            $userManager->updateUser($user);

            $data = $request->request->get('cssr_mainbundle_staff_update_type');
            Staff::updateCourses($em,$user,array($data['area']));

            $this->get('session')->getFlashBag()->add(
                'success',
                'User updated successfully!'
            );

            return $this->redirect($this->generateUrl('staff_edit', array('id' => $id)));
        }

        return array(
            'user'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a staff entity.
     *
     * @Route("/{id}", name="staff_delete")
     * @Method("DELETE")
     */
    public function deleteAction ( Request $request, $id )
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('CssrMainBundle:User')->find($id);

        if ( !$user ) {
            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'failed';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                throw $this->createNotFoundException('Unable to find Staff.');
            }
        }

        // cancel courses
        Staff::cancelCourses($em,$user);
        $user->setEnabled(0); // logical delete

        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',
            'Staff deleted successfully!'
        );

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'success';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            return $this->redirect($this->generateUrl('staff'));
        }
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

        $courses = Staff::getCourses($em,$entity);
        $students = Staff::getStudents($em,$entity->getId());

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
            'courses' => $courses,
            'students' => $students
        );
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
