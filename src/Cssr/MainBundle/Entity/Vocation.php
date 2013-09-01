<?php

namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_vocation")
 */
class Vocation
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
     * @ORM\ManyToOne(targetEntity="Center", inversedBy="vocations")
     * @ORM\JoinColumn(name="center_id", referencedColumnName="id")
     */
    protected $center;


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
     * @return Vocation
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
     * Set center
     *
     * @param \Cssr\MainBundle\Entity\Center $center
     * @return Center
     */
    public function setCenter(\Cssr\MainBundle\Entity\Center $center)
    {
        $this->center = $center;
    
        return $this;
    }

    /**
     * Get center
     *
     * @return \Cssr\MainBundle\Entity\Center 
     */
    public function getCenter()
    {
        return $this->center;
    }
}