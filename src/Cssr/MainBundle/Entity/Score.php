<?php

namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_score")
 */
class Score
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $value;

    /**
     * @ORM\OneToOne(targetEntity="Course")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @ORM\OneToOne(targetEntity="Student")
     * @ORM\JoinColumn(name="student_id", referencedColumnName="id")
     */
    protected $student;

    /**
     * @ORM\ManyToMany(targetEntity="Standard")
     * @ORM\JoinTable(name="cssr_score_standard",
     *      joinColumns={@ORM\JoinColumn(name="score_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="standard_id", referencedColumnName="id", unique=true)}
     *      )
     **/
    protected $standards;

    /**
     * @ORM\Column(type="text")
     */
    protected $comment;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $modified;

    /**
     * @ORM\OneToOne(targetEntity="Staff")
     * @ORM\JoinColumn(name="modifier_id", referencedColumnName="id")
     */
    protected $modifier;

    /**
     * @ORM\PrePersist
     */
    public function setCreatedValue()
    {
        $this->created = new \DateTime();
    }

    /**
     * @ORM\preUpdate
     */
    public function setModifiedValue()
    {
        $this->modified = new \DateTime();
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
     * Set value
     *
     * @param string $value
     * @return Score
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->standards = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set comment
     *
     * @param string $comment
     * @return Score
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    
        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Score
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     * @return Score
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    
        return $this;
    }

    /**
     * Get modified
     *
     * @return \DateTime 
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set course
     *
     * @param \Cssr\MainBundle\Entity\Course $course
     * @return Score
     */
    public function setCourse(\Cssr\MainBundle\Entity\Course $course = null)
    {
        $this->course = $course;
    
        return $this;
    }

    /**
     * Get course
     *
     * @return \Cssr\MainBundle\Entity\Course 
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set student
     *
     * @param \Cssr\MainBundle\Entity\Student $student
     * @return Score
     */
    public function setStudent(\Cssr\MainBundle\Entity\Student $student = null)
    {
        $this->student = $student;
    
        return $this;
    }

    /**
     * Get student
     *
     * @return \Cssr\MainBundle\Entity\Student 
     */
    public function getStudent()
    {
        return $this->student;
    }

    /**
     * Add standards
     *
     * @param \Cssr\MainBundle\Entity\Standard $standards
     * @return Score
     */
    public function addStandard(\Cssr\MainBundle\Entity\Standard $standards)
    {
        $this->standards[] = $standards;
    
        return $this;
    }

    /**
     * Remove standards
     *
     * @param \Cssr\MainBundle\Entity\Standard $standards
     */
    public function removeStandard(\Cssr\MainBundle\Entity\Standard $standards)
    {
        $this->standards->removeElement($standards);
    }

    /**
     * Get standards
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getStandards()
    {
        return $this->standards;
    }

    /**
     * Set modifier
     *
     * @param \Cssr\MainBundle\Entity\Staff $modifier
     * @return Score
     */
    public function setModifier(\Cssr\MainBundle\Entity\Staff $modifier = null)
    {
        $this->modifier = $modifier;
    
        return $this;
    }

    /**
     * Get modifier
     *
     * @return \Cssr\MainBundle\Entity\Staff 
     */
    public function getModifier()
    {
        return $this->modifier;
    }
}