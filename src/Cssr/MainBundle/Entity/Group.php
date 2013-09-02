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


    public function __construct() {
        $this->users = new ArrayCollection();
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

}