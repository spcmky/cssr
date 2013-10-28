<?php

namespace Cssr\MainBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Cssr\MainBundle\Entity\Comment;
use Cssr\MainBundle\Entity\Standard;


/**
 * Comment controller.
 *
 * @Route("/comment")
 */
class CommentController extends Controller
{

    /**
     * Creates a new Comment entity.
     *
     * @Route("/", name="comment_create")
     * @Method("POST")
     */
    public function createAction ( Request $request ) {

        $em = $this->getDoctrine()->getManager();

        $comment  = new Comment();

        $isValid = true;

        if ( !empty($_POST['score']) ) {
            $score = $em->getRepository('CssrMainBundle:Score')->find($_POST['score']);
            if ( $score ) {
                $comment->setScore($score);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( !empty($_POST['comment']) ) {
            $comment->setComment($_POST['comment']);
        } else {
            $isValid = false;
        }

        if ( !empty($_POST['standards']) ) {
            foreach ( $_POST['standards'] as $sid ) {
                $standard = $em->getRepository('CssrMainBundle:Standard')->find($sid);
                if ( $standard ) {
                    $comment->addStandard($standard);
                }
            }
        }

        if ( $isValid ) {

            $comment->setCreatedBy($this->getUser());
            $comment->setUpdatedBy($this->getUser());

            $em->persist($comment);
            $em->flush();

            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'success';
                $api_response->commentId = $comment->getId();

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                //return $this->redirect($this->generateUrl('comment_show', array('id' => $comment->getId())));
            }
        }

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';
            $api_response->data = $_POST;


            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return array(
            'entity' => $comment,
            //'form'   => $form->createView(),
        );
    }

    /**
     * Edits an existing Comment entity.
     *
     * @Route("/{id}", name="comment_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Comment:edit.html.twig")
     */
    public function updateAction ( Request $request, $id ) {

        $em = $this->getDoctrine()->getManager();

        $comment = $em->getRepository('CssrMainBundle:Comment')->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Unable to find Comment entity.');
        }

        $isValid = true;

        $body = $request->request->get('comment');
        $comment->setComment($body);

        $standards = $request->request->get('standards');

        if ( !empty($standards) ) {
            $standardCollection = new ArrayCollection();
            foreach ( $standards as $sid ) {
                $standard = $em->getRepository('CssrMainBundle:Standard')->find($sid);
                if ( $standard ) {
                    $standardCollection->add($standard);
                }
            }
            $comment->setStandards($standardCollection);
        }

        if ( $isValid ) {

            $comment->setUpdatedBy($this->getUser());

            $em->persist($comment);
            $em->flush();

            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'success';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        }

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return array(
            'entity'      => $comment,
            //'edit_form'   => $editForm->createView(),
            //'delete_form' => $deleteForm->createView(),
        );

    }
}
