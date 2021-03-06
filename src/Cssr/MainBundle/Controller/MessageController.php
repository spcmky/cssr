<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Cssr\MainBundle\Entity\Message;
use Cssr\MainBundle\Form\MessageType;

/**
 * Message controller.
 *
 * @Route("/message")
 */
class MessageController extends Controller
{

    /**
     * Lists all Messages.
     *
     * @Route("/", name="message")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        //findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)

        $messages = $em->getRepository('CssrMainBundle:Message')->findBy(array('active'=>1),array('updated'=>'desc'));

        return array(
            'messages' => $messages,
        );
    }
    /**
     * Creates a new Message.
     *
     * @Route("/", name="message_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Message:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $message = new Message();
        $form = $this->createCreateForm($message);
        $form->handleRequest($request);

        if ( $form->isValid() ) {

            $em = $this->getDoctrine()->getManager();

            $message->setActive(1);
            $message->setCreatedBy($this->getUser());
            $message->setUpdatedBy($this->getUser());

            $em->persist($message);
            $em->flush();

            // add the groups
            $sql  = 'INSERT INTO cssr_group_message ( group_id, message_id ) ';
            $sql .= 'VALUES ( :group, :message ) ';
            $stmt = $em->getConnection()->prepare($sql);

            foreach ( $message->getGroups() as $group ) {
                $stmt->bindValue('group', $group->getId(), \PDO::PARAM_INT);
                $stmt->bindValue('message', $message->getId(), \PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->get('session')->getFlashBag()->add(
                'success',
                'Message created successfully!'
            );

            return $this->redirect($this->generateUrl('message'));
        }

        return array(
            'message' => $message,
            'form'   => $form->createView(),
        );
    }

    /**
    * Creates a form to create a Message.
    *
    * @param Message $message The message
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(Message $message)
    {

        $form = $this->createForm(new MessageType($this->getDoctrine()->getManager()), $message, array(
            'action' => $this->generateUrl('message_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Message.
     *
     * @Route("/new", name="message_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $message = new Message();
        $form   = $this->createCreateForm($message);

        return array(
            'message' => $message,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Message.
     *
     * @Route("/{id}", name="message_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $message = $em->getRepository('CssrMainBundle:Message')->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Unable to find Message.');
        }

        return array(
            'message'      => $message
        );
    }

    /**
     * Displays a form to edit an existing Message.
     *
     * @Route("/{id}/edit", name="message_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $message = $em->getRepository('CssrMainBundle:Message')->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Unable to find Message.');
        }

        $editForm = $this->createEditForm($message);

        return array(
            'message'      => $message,
            'edit_form'   => $editForm->createView()
        );
    }

    /**
    * Creates a form to edit a Message.
    *
    * @param Message $message The message
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Message $message)
    {
        $form = $this->createForm(new MessageType($this->getDoctrine()->getManager()), $message, array(
            'action' => $this->generateUrl('message_update', array('id' => $message->getId())),
            'method' => 'PUT',
        ));

        return $form;
    }

    /**
     * Edits an existing Message.
     *
     * @Route("/{id}", name="message_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Message:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $message = $em->getRepository('CssrMainBundle:Message')->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Unable to find Message.');
        }

        $editForm = $this->createEditForm($message);
        $editForm->handleRequest($request);

        if ( $editForm->isValid() ) {

            $em->flush();

            // remove old groups
            $sql  = 'DELETE FROM cssr_group_message WHERE message_id = '.$message->getId().' ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();

            // add the new groups
            $sql  = 'INSERT INTO cssr_group_message ( group_id, message_id ) ';
            $sql .= 'VALUES ( :group, :message ) ';
            $stmt = $em->getConnection()->prepare($sql);

            foreach ( $message->getGroups() as $group ) {
                $stmt->bindValue('group', $group->getId(), \PDO::PARAM_INT);
                $stmt->bindValue('message', $message->getId(), \PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->get('session')->getFlashBag()->add(
                'success',
                'Message updated successfully!'
            );

            return $this->redirect($this->generateUrl('message_edit', array('id' => $id)));
        }

        return array(
            'message'      => $message,
            'edit_form'   => $editForm->createView()
        );
    }
    /**
     * Deletes a Message.
     *
     * @Route("/{id}", name="message_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $message = $em->getRepository('CssrMainBundle:Message')->find($id);

        if ( !$message ) {
            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'failed';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                throw $this->createNotFoundException('Unable to find Message.');
            }
        }

        $message->setActive(0); // logical delete

        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',
            'Message deleted successfully!'
        );

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'success';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            return $this->redirect($this->generateUrl('message'));
        }
    }
}
