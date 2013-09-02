<?php

namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_comment")
 */
class Comment
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
    protected $name;

    /**
     * @ORM\OneToOne(targetEntity="Score", inversedBy="comment")
     * @ORM\JoinColumn(name="score_id", referencedColumnName="id")
     */
    protected $score;

    /**
     * @ORM\ManyToMany(targetEntity="Standard")
     * @ORM\JoinTable(name="cssr_comment_standard",
     *      joinColumns={@ORM\JoinColumn(name="comment_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="standard_id", referencedColumnName="id")}
     *      )
     **/
    protected $standards;

    public function __construct()
    {
        $this->standards = new ArrayCollection();
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
}