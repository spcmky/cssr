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



}