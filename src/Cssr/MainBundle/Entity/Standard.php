<?php

namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_standard")
 */
class Standard
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Cssr\MainBundle\Entity\Comment", mappedBy="standards")
     */
    protected $comments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->comments = new ArrayCollection();
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
     * @return Standard
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
     * Add comments
     *
     * @param \Cssr\MainBundle\Entity\Comment $comment
     * @return Center
     */
    public function addComment(\Cssr\MainBundle\Entity\Comment $comment)
    {
        $this->comments->add($comment);

        return $this;
    }

    /**
     * Remove comments
     *
     * @param \Cssr\MainBundle\Entity\Comment $comment
     */
    public function removeComment(\Cssr\MainBundle\Entity\Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set comments
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $comments
     */
    public function setComments(ArrayCollection $comments)
    {
        $this->comments = $comments;
    }
}