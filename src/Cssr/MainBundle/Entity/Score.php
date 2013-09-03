<?php

namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_score")
 * @ORM\HasLifecycleCallbacks()

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
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="student_id", referencedColumnName="id")
     */
    protected $student;

    /**
     * @ORM\OneToOne(targetEntity="Comment")
     * @ORM\JoinColumn(name="comment_id", referencedColumnName="id")
     */
    protected $comment;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     **/
    private $createdBy;

    /**
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     **/
    private $updatedBy;

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
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
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
     * Set updated
     *
     * @param \DateTime $updated
     * @return Score
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    
        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
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
     * Set createdBy
     *
     * @param \Cssr\MainBundle\Entity\User $user
     * @return Score
     */
    public function setCreatedBy(\Cssr\MainBundle\Entity\User $user)
    {
        $this->createdBy = $user;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Cssr\MainBundle\Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set updatedBy
     *
     * @param \Cssr\MainBundle\Entity\User $user
     * @return Score
     */
    public function setUpdatedBy(\Cssr\MainBundle\Entity\User $user)
    {
        $this->updatedBy = $user;

        return $this;
    }

    /**
     * Get updatedBy
     *
     * @return \Cssr\MainBundle\Entity\User
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Add comment
     *
     * @param \Cssr\MainBundle\Entity\Comment $comment
     * @return Score
     */
    public function setComment(\Cssr\MainBundle\Entity\Comment $comment)
    {
        $comment->setScore($this);
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return \Cssr\MainBundle\Entity\Comment
     */
    public function getComment()
    {
        return $this->comment;
    }

}