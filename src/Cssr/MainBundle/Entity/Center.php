<?php
namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_center")
 * @ORM\HasLifecycleCallbacks()
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
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $name;

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
     * @ORM\OneToMany(targetEntity="Dorm", mappedBy="center", cascade={"persist"})
     **/
    protected $dorms;

    /**
     * @ORM\OneToMany(targetEntity="Vocation", mappedBy="center", cascade={"persist"})
     **/
    protected $vocations;

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
    protected $createdBy;

    /**
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     **/
    protected $updatedBy;

    /**
     * @ORM\Column(type="integer")
     */
    protected $active;

    public function __construct()
    {
        $this->dorms = new ArrayCollection();
        $this->vocations = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->active = 1;
        $this->created = $this->updated = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
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
     * Set address
     *
     * @param text $address
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
        $dorm->setCenter($this);
        $this->dorms->add($dorm);
    
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
     * Set dorms
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $dorms
     */
    public function setDorms(ArrayCollection $dorms)
    {
        $this->dorms = $dorms;
    }

    /**
     * Add vocations
     *
     * @param \Cssr\MainBundle\Entity\Vocation $vocation
     * @return Center
     */
    public function addVocation(\Cssr\MainBundle\Entity\Vocation $vocation)
    {
        $vocation->setCenter($this);
        $this->vocations->add($vocation);
    
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

    /**
     * Set vocations
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $vocations
     */
    public function setVocations(ArrayCollection $vocations)
    {
        $this->vocations = $vocations;
    }

    /**
     * Set createdBy
     *
     * @param \Cssr\MainBundle\Entity\User $user
     * @return Center
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
     * @return Center
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
     * Get active
     *
     * @return integer
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set active
     *
     * @param integer $active
     * @return Center
     */
    public function setActive($active)
    {
        $this->active = $active;

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
     * Set created
     *
     * @param \DateTime $created
     * @return Center
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

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
     * Set updated
     *
     * @param \DateTime $updated
     * @return Center
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    public function __toString() {
        return $this->getName();
    }
}