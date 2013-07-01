<?php

namespace Cssr\MainBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="cssr_student")
*/
class Student
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
     * @ORM\OneToOne(targetEntity="Dorm")
     * @ORM\JoinColumn(name="dorm_id", referencedColumnName="id")
     */
    protected $dorm;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $entry;

    /**
     * @ORM\ManyToOne(targetEntity="Course")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id")
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