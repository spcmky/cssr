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
     * @ORM\ManyToOne(targetEntity="Staff", inversedBy="courses")
     * @ORM\JoinColumn(name="staff_id", referencedColumnName="id")
     */
    protected $staff;

    /**
     * @ORM\OneToOne(targetEntity="Area")
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
     * @param \Cssr\MainBundle\Entity\Staff $staff
     * @return Course
     */
    public function setStaff(\Cssr\MainBundle\Entity\Staff $staff = null)
    {
        $this->staff = $staff;
    
        return $this;
    }

    /**
     * Get staff
     *
     * @return \Cssr\MainBundle\Entity\Staff 
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
    /**
     * Constructor
     */
    public function __construct()
    {
    }

}