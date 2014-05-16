<?php

namespace Cssr\MainBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\User;
use Cssr\MainBundle\Form\UserType;
use Cssr\MainBundle\Form\AdminCreateType;
use Cssr\MainBundle\Form\AdminUpdateType;
use Cssr\MainBundle\Model\Group;

/**
 * User controller.
 *
 * @Route("/user")
 */
class UserController extends Controller {

    /**
     * Lists all User entities.
     *
     * @Route("/", name="user")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {

        $em = $this->getDoctrine()->getManager();

        $sql  = "SELECT U.*, G.name group_id, G.name group_name, C.id center_id, C.name center_name ";
        $sql .= "FROM cssr_user_group UG ";
        $sql .= "LEFT JOIN cssr_user U ON U.id = UG.user_id ";
        $sql .= "LEFT JOIN cssr_group G ON G.id = UG.group_id ";
        $sql .= "LEFT JOIN cssr_center C ON C.id = U.center_id ";
        $sql .= "WHERE UG.group_id < 5 AND U.enabled = 1 ";

        $stmt = $em->getConnection()->prepare($sql);

        $stmt->execute();

        $result = $stmt->fetchAll();

        return array(
            'users' => $result
        );
    }

    /**
     * Lists all User entities.
     *
     * @Route("/admin", name="user_admin")
     * @Method("GET")
     * @Template()
     */
    public function indexAdminAction()
    {
        $em = $this->getDoctrine()->getManager();

        if ( Group::isGranted($this->getUser(),'center create') ) {
            $sql  = "SELECT U.*, G.id group_id, G.name group_name, C.id center_id, C.name center_name ";
            $sql .= "FROM cssr_user_group UG ";
            $sql .= "LEFT JOIN cssr_user U ON U.id = UG.user_id ";
            $sql .= "LEFT JOIN cssr_group G ON G.id = UG.group_id ";
            $sql .= "LEFT JOIN cssr_center C ON C.id = U.center_id ";
            $sql .= "WHERE UG.group_id < 5 AND U.enabled = 1 ";
            $sql .= "ORDER BY U.lastname, U.firstname, U.middlename ";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll();
        } else if ( Group::isGranted($this->getUser(),'center update') ) {
            $session = $this->getRequest()->getSession();
            $center = $em->getRepository('CssrMainBundle:Center')->find($session->get('center')->id);

            $sql  = "SELECT U.*, G.id group_id, G.name group_name, C.id center_id, C.name center_name ";
            $sql .= "FROM cssr_user_group UG ";
            $sql .= "LEFT JOIN cssr_user U ON U.id = UG.user_id ";
            $sql .= "LEFT JOIN cssr_group G ON G.id = UG.group_id ";
            $sql .= "LEFT JOIN cssr_center C ON C.id = U.center_id ";
            $sql .= "WHERE UG.group_id < 5 AND UG.group_id > 1 AND U.enabled = 1 AND U.center_id = ".$center->getId()." ";
            $sql .= "ORDER BY U.lastname, U.firstname, U.middlename ";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll();
        }

        return array(
            'user' => $this->getUser(),
            'users' => $users
        );
    }

    /**
     * Lists all User entities.
     *
     * @Route("/groups", name="user_setup")
     * @Method("GET")
     * @Template()
     */
    public function userSetupAction()
    {
        $em = $this->getDoctrine()->getManager();

        $sql = "SELECT * FROM cssr_group ORDER BY id";
        $stmt = $em->getConnection()->prepare($sql);

        $stmt->execute();

        $result = $stmt->fetchAll();

        return array(
            'user' => $this->getUser(),
            'groups' => $result
        );
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/", name="user_create")
     * @Method("POST")
     * @Template("CssrMainBundle:User:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $params = $request->request->get('cssr_mainbundle_usertype');

        $em = $this->getDoctrine()->getManager();

        $group = $em->getRepository('CssrMainBundle:Group')->find($params['group']);
        $groups = new ArrayCollection();
        $groups->add($group);
        $user->setGroups($groups);

        $form = $this->createForm(new UserType(array(
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centers' => $em->getRepository('CssrMainBundle:Center')->findAll(),
            'group' => array()
        )), $user);

        $form->submit($request);

        if ( $form->isValid() ) {
            $user->setEmail(time().'@fake.com');
            $user->setEnabled(true);
            $userManager->updateUser($user);

            return $this->redirect($this->generateUrl('user_show', array('id' => $user->getId())));
        }

        return array(
            'entity' => $user,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/admin", name="user_admin_create")
     * @Method("POST")
     * @Template("CssrMainBundle:User:newAdmin.html.twig")
     */
    public function createAdminAction ( Request $request )
    {
        $params = $request->request->get('cssr_mainbundle_admin_create_type');

        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $em->getRepository('CssrMainBundle:Center')->find($session->get('center')->id);

        $group = $em->getRepository('CssrMainBundle:Group')->find($params['group']);

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $user->setEnabled(true);
        $user->addGroup($group);

        if ( $group->getId() > 1 ) {
            $user->setCenter($center);
        }

        $form = $this->createForm(new AdminCreateType(array(
            'group' => $group
        )), $user);

        $form->submit($request);

        if ( $form->isValid() ) {

            $user->setEnabled(true);
            $userManager->updateUser($user);

            $this->get('session')->getFlashBag()->add(
                'success',
                $group->getName().' created successfully!'
            );

            return $this->redirect($this->generateUrl('user_admin'));
        }

        return array(
            'center' => $center,
            'group' => $group,
            'user' => $user,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new User entity.
     *
     * @Route("/new", name="user_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $user->setEnabled(true);

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new UserType(array(
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centers' => $em->getRepository('CssrMainBundle:Center')->findAll(),
            'group' => array()
        )), $user);

        return array(
            'entity' => $user,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new User entity.
     *
     * @Route("/admin/new/{groupId}", name="user_admin_new")
     * @Method("GET")
     * @Template()
     */
    public function newAdminAction ( $groupId )
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $em->getRepository('CssrMainBundle:Center')->find($session->get('center')->id);

        $group = $em->getRepository('CssrMainBundle:Group')->find($groupId);

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $user->setEnabled(true);
        $user->addGroup($group);

        if ( $group->getId() > 1 ) {
            $user->setCenter($center);
        }

        $form = $this->createForm(new AdminCreateType(array(
            'group' => $group
        )), $user);

        return array(
            'center' => $center,
            'group' => $group,
            'user' => $user,
            'form' => $form->createView()
        );
    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}", name="user_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/admin/{id}", name="user_admin_show")
     * @Method("GET")
     * @Template()
     */
    public function showAdminAction ( $id ) {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('CssrMainBundle:User')->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User.');
        }

        return array(
            'user'      => $user
        );
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->findUserBy(array('id'=>$id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $em = $this->getDoctrine()->getManager();

        $editForm = $this->createForm(new UserType(array(
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centers' => $em->getRepository('CssrMainBundle:Center')->findAll(),
            'group' => $user->getGroups()
        )), $user);

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/admin/{id}/edit", name="user_admin_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAdminAction ( $id )
    {
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->findUserBy(array('id'=>$id));

        if ( !$user ) {
            throw $this->createNotFoundException('Unable to find User.');
        }

        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $em->getRepository('CssrMainBundle:Center')->find($session->get('center')->id);

        $group = $user->getFirstGroup();

        $editForm = $this->createForm(new AdminUpdateType(array(
            'group' => $group
        )), $user);

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'group' => $group,
            'center' => $center,
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );
    }

    /**
     * Edits an existing User entity.
     *
     * @Route("/{id}", name="user_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:User:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id'=>$id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        $em = $this->getDoctrine()->getManager();

        $editForm = $this->createForm(new UserType(array(
            'groups' => $em->getRepository('CssrMainBundle:Group')->findAll(),
            'centers' => $em->getRepository('CssrMainBundle:Center')->findAll(),
            'group' => $user->getGroups()
        )), $user);

        $editForm->submit($request);

        if ( $editForm->isValid() ) {

            $userManager->updateUser($user);

            $this->get('session')->getFlashBag()->add(
                'success',
                'Updated successfully!'
            );

            return $this->redirect($this->generateUrl('user_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing User entity.
     *
     * @Route("/admin/{id}", name="user_admin_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:User:editAdmin.html.twig")
     */
    public function updateAdminAction(Request $request, $id)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id'=>$id));

        if ( !$user ) {
            throw $this->createNotFoundException('Unable to find User.');
        }

        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $em->getRepository('CssrMainBundle:Center')->find($session->get('center')->id);

        $group = $user->getFirstGroup();

        $editForm = $this->createForm(new AdminUpdateType(array(
            'group' => $group
        )), $user);

        $deleteForm = $this->createDeleteForm($id);

        $editForm->submit($request);

        if ( $editForm->isValid() ) {

            $userManager->updateUser($user);

            $this->get('session')->getFlashBag()->add(
                'success',
                $group->getName().' updated successfully!'
            );

            return $this->redirect($this->generateUrl('user_admin'));
        }

        return array(
            'group' => $group,
            'center' => $center,
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );
    }

    /**
     * Deletes a User entity.
     *
     * @Route("/{id}", name="user_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CssrMainBundle:User')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('user'));
    }

    /**
     * Deletes a User entity.
     *
     * @Route("/admin/{id}", name="user_admin_delete")
     * @Method("DELETE")
     */
    public function deleteAdminAction ( Request $request, $id )
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
                throw $this->createNotFoundException('Unable to find User.');
            }
        }

        $group = $user->getFirstGroup();

        $user->setEnabled(0); // logical delete

        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',
            $group->getName().' deleted successfully!'
        );

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'success';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            return $this->redirect($this->generateUrl('user_admin'));
        }
    }

    /**
     * Creates a form to delete a User entity by id.
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
