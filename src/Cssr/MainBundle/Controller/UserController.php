<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Cssr\MainBundle\Entity\User;
use Cssr\MainBundle\Form\UserType;

/**
 * User controller.
 *
 * @Route("/user")
 */
class UserController extends Controller
{

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

        $sql = "SELECT U.*, G.name group_id, G.name group_name, C.id center_id, C.name center_name FROM cssr_user_group UG
        LEFT JOIN cssr_user U ON U.id = UG.user_id
        LEFT JOIN cssr_group G ON G.id = UG.group_id
        LEFT JOIN cssr_center C ON C.id = U.center_id
        WHERE UG.group_id IN (1,2,3,4)";

        $stmt = $em->getConnection()->prepare($sql);

        $stmt->execute();

        $result = $stmt->fetchAll();

        return array(
            'entities' => $result
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
        $user->setEnabled(true);

        $form = $this->createForm(new UserType(), $user);
        $form->submit($request);

        if ( $form->isValid() ) {

            $userManager->updateUser($user);

            return $this->redirect($this->generateUrl('user_show', array('id' => $user->getId())));
        }

        return array(
            'entity' => $user,
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

        $form   = $this->createForm(new UserType(), $user);

        return array(
            'entity' => $user,
            'form'   => $form->createView(),
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

        $editForm = $this->createForm(new UserType(), $user);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
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
        $editForm = $this->createForm(new UserType(), $user);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $userManager->updateUser($user);

            return $this->redirect($this->generateUrl('user_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
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
        $form->bind($request);

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
