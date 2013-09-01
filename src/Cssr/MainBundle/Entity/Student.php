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
     * @ORM\ManyToMany(targetEntity="Course")
     * @ORM\JoinTable(name="cssr_student_course",
     *      joinColumns={@ORM\JoinColumn(name="course_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="student_id", referencedColumnName="id", unique=true)}
     *      )
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



    /**
     * Set firstname
     *
     * @param string $firstname
     * @return Student
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    
        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set middlename
     *
     * @param string $middlename
     * @return Student
     */
    public function setMiddlename($middlename)
    {
        $this->middlename = $middlename;
    
        return $this;
    }

    /**
     * Get middlename
     *
     * @return string 
     */
    public function getMiddlename()
    {
        return $this->middlename;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Student
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    
        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set entry
     *
     * @param \DateTime $entry
     * @return Student
     */
    public function setEntry($entry)
    {
        $this->entry = $entry;
    
        return $this;
    }

    /**
     * Get entry
     *
     * @return \DateTime 
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * Set dorm
     *
     * @param \Cssr\MainBundle\Entity\Dorm $dorm
     * @return Student
     */
    public function setDorm(\Cssr\MainBundle\Entity\Dorm $dorm = null)
    {
        $this->dorm = $dorm;
    
        return $this;
    }

    /**
     * Get dorm
     *
     * @return \Cssr\MainBundle\Entity\Dorm 
     */
    public function getDorm()
    {
        return $this->dorm;
    }

    /**
     * Set courses
     *
     * @param \Cssr\MainBundle\Entity\Course $courses
     * @return Student
     */
    public function setCourses(\Cssr\MainBundle\Entity\Course $courses = null)
    {
        $this->courses = $courses;
    
        return $this;
    }

    /**
     * Get courses
     *
     * @return \Cssr\MainBundle\Entity\Course 
     */
    public function getCourses()
    {
        return $this->courses;
    }

    /**
     * Add courses
     *
     * @param \Cssr\MainBundle\Entity\Course $courses
     * @return Student
     */
    public function addCourse(\Cssr\MainBundle\Entity\Course $courses)
    {
        $this->courses[] = $courses;
    
        return $this;
    }

    /**
     * Remove courses
     *
     * @param \Cssr\MainBundle\Entity\Course $courses
     */
    public function removeCourse(\Cssr\MainBundle\Entity\Course $courses)
    {
        $this->courses->removeElement($courses);
    }
}