<?php

namespace Cssr\MainBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_staff")
 */
class Staff
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $firstname;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $middlename;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $lastname;

    /**
     * @ORM\OneToMany(targetEntity="Course", mappedBy="staff")
     */
    protected $courses;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
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

}