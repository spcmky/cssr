<?php

namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_comment")
 * @ORM\HasLifecycleCallbacks()
 */
class Comment
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=4000, nullable=true)
     */
    private $comment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $created;

    /**
     *
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     **/
    private $updatedBy;

    /**
     * @var \Cssr\MainBundle\Entity\Score
     *
     * @ORM\ManyToOne(targetEntity="Cssr\MainBundle\Entity\Score")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="score_id", referencedColumnName="id")
     * })
     */
    private $score;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Cssr\MainBundle\Entity\Standard", inversedBy="comments")
     * @ORM\JoinTable(name="cssr_comment_standard",
     *   joinColumns={
     *     @ORM\JoinColumn(name="comment_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="standard_id", referencedColumnName="id")
     *   }
     * )
     */
    private $standards;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->standards = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedValue()
    {
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
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
     * Set name
     *
     * @param string $name
     * @return Comment
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Comment
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

    public function __toString() {
        return $this->getName();
    }

    /**
     * Set score
     *
     * @param \Cssr\MainBundle\Entity\Score $score
     * @return Comment
     */
    public function setScore(\Cssr\MainBundle\Entity\Score $score)
    {
        $this->score = $score;
    
        return $this;
    }

    /**
     * Get score
     *
     * @return \Cssr\MainBundle\Entity\Score
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Add standards
     *
     * @param \Cssr\MainBundle\Entity\Standard $standard
     * @return Center
     */
    public function addStandard(\Cssr\MainBundle\Entity\Standard $standard)
    {
        $this->standards->add($standard);

        return $this;
    }

    /**
     * Remove standards
     *
     * @param \Cssr\MainBundle\Entity\Standard $standard
     */
    public function removeStandard(\Cssr\MainBundle\Entity\Standard $standard)
    {
        $this->standards->removeElement($standard);
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
     * Set standards
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $standards
     */
    public function setStandards(ArrayCollection $standards)
    {
        $this->standards = $standards;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Comment
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
     * @return Comment
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
     * Set createdBy
     *
     * @param \Cssr\MainBundle\Entity\User $user
     * @return Comment
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
     * @return Comment
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

}