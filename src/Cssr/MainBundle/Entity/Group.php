<?php

namespace Cssr\MainBundle\Entity;

use FOS\UserBundle\Model\Group as BaseGroup;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_group")
 */
class Group extends BaseGroup
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groups")
     **/
    protected $users;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Cssr\MainBundle\Entity\Message", inversedBy="groups")
     * @ORM\JoinTable(name="cssr_group_message",
     *   joinColumns={
     *     @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="message_id", referencedColumnName="id")
     *   }
     * )
     */
    protected $messages;


    public function __construct() {
        $this->users = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Add users
     *
     * @param \Cssr\MainBundle\Entity\User $user
     * @return Center
     */
    public function addUser(\Cssr\MainBundle\Entity\User $user)
    {
        $user->addGroup($this);
        $this->users->add($user);

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Cssr\MainBundle\Entity\User $user
     */
    public function removeUser(\Cssr\MainBundle\Entity\User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set users
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $users
     */
    public function setUsers(ArrayCollection $users)
    {
        $this->users = $users;
    }

    /**
     * Add message
     *
     * @param \Cssr\MainBundle\Entity\Message $message
     * @return Group
     */
    public function addMessage(\Cssr\MainBundle\Entity\Message $message)
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Remove message
     *
     * @param \Cssr\MainBundle\Entity\Message $message
     */
    public function removeMessage(\Cssr\MainBundle\Entity\Message $message)
    {
        $this->messages->removeElement($message);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Set messages
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $messages
     */
    public function setMessages(ArrayCollection $messages)
    {
        $this->messages = $messages;
    }

    /*
     * @return string
     */
    public function __toString () {
        return $this->getName();
    }
}