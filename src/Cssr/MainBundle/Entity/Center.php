<?php
namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_center")
 */
class Center
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=250, nullable=true)
     */
    protected $address;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $city;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $state;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $postcode;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $phone;

    /**
     * @ORM\ManyToMany(targetEntity="Dorm")
     * @ORM\JoinTable(name="cssr_center_dorm",
     *      joinColumns={@ORM\JoinColumn(name="center_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="dorm_id", referencedColumnName="id", unique=true)}
     *      )
     **/
    protected $dorms;

    /**
     * @ORM\ManyToMany(targetEntity="Vocation")
     * @ORM\JoinTable(name="cssr_center_vocation",
     *      joinColumns={@ORM\JoinColumn(name="center_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="vocation_id", referencedColumnName="id", unique=true)}
     *      )
     **/
    protected $vocations;

    public function __construct()
    {
        $this->dorms = new ArrayCollection();
        $this->vocations = new ArrayCollection();
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
     * @return Center
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
     * Set description
     *
     * @param string $description
     * @return Center
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return Center
     */
    public function setAddress($address)
    {
        $this->address = $address;
    
        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Center
     */
    public function setCity($city)
    {
        $this->city = $city;
    
        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Center
     */
    public function setState($state)
    {
        $this->state = $state;
    
        return $this;
    }

    /**
     * Get state
     *
     * @return string 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set postcode
     *
     * @param string $postcode
     * @return Center
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    
        return $this;
    }

    /**
     * Get postcode
     *
     * @return string 
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Center
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    
        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Add dorm
     *
     * @param \Cssr\MainBundle\Entity\Dorm $dorm
     * @return Center
     */
    public function addDorm(\Cssr\MainBundle\Entity\Dorm $dorm)
    {
        $this->dorms[] = $dorm;
    
        return $this;
    }

    /**
     * Remove dorm
     *
     * @param \Cssr\MainBundle\Entity\Dorm $dorm
     */
    public function removeDorm(\Cssr\MainBundle\Entity\Dorm $dorm)
    {
        $this->dorms->removeElement($dorm);
    }

    /**
     * Get dorms
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDorms()
    {
        return $this->dorms;
    }

    /**
     * Add vocations
     *
     * @param \Cssr\MainBundle\Entity\Vocation $vocation
     * @return Center
     */
    public function addVocation(\Cssr\MainBundle\Entity\Vocation $vocation)
    {
        $this->vocations[] = $vocation;
    
        return $this;
    }

    /**
     * Remove vocations
     *
     * @param \Cssr\MainBundle\Entity\Vocation $vocation
     */
    public function removeVocation(\Cssr\MainBundle\Entity\Vocation $vocation)
    {
        $this->vocations->removeElement($vocation);
    }

    /**
     * Get vocations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getVocations()
    {
        return $this->vocations;
    }
}