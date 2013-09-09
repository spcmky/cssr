<?php

namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_course")
 */
class Course
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="courses")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $staff;

    /**
     * @ORM\ManyToOne(targetEntity="Area")
     * @ORM\JoinColumn(name="area_id", referencedColumnName="id")
     */
    protected $area;


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
     * Set staff
     *
     * @param \Cssr\MainBundle\Entity\User $staff
     * @return Course
     */
    public function setStaff(\Cssr\MainBundle\Entity\User $staff = null)
    {
        $this->staff = $staff;
    
        return $this;
    }

    /**
     * Get staff
     *
     * @return \Cssr\MainBundle\Entity\User
     */
    public function getStaff()
    {
        return $this->staff;
    }

    /**
     * Set area
     *
     * @param \Cssr\MainBundle\Entity\Area $area
     * @return Course
     */
    public function setArea(\Cssr\MainBundle\Entity\Area $area = null)
    {
        $this->area = $area;
    
        return $this;
    }

    /**
     * Get area
     *
     * @return \Cssr\MainBundle\Entity\Area 
     */
    public function getArea()
    {
        return $this->area;
    }

}