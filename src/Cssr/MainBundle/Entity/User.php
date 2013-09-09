<?php
namespace Cssr\MainBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * @ORM\Entity
 * @ORM\Table(name="cssr_user")
 * @ORM\Entity(repositoryClass="Cssr\MainBundle\Entity\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Center")
     * @ORM\JoinColumn(name="center_id", referencedColumnName="id")
     */
    protected $center;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $firstname;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $middlename;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $lastname;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $phone;

    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users")
     * @ORM\JoinTable(name="cssr_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $entry;

    /**
     * @ORM\ManyToOne(targetEntity="Dorm")
     * @ORM\JoinColumn(name="dorm_id", referencedColumnName="id")
     */
    protected $dorm;

    public function __construct ( $values = array() )
    {
        parent::__construct();

        foreach ( $values as $key => $value ) {
            $this->$key = $value;
        }

        $this->groups = new ArrayCollection();
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
     * Set id
     *
     * @param integer $id
     * @return User
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return User
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
     * @return User
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
     * @return User
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
     * Set phone
     *
     * @param string $phone
     * @return User
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
     * Get center
     *
     * @return Center
     */
    public function getCenter()
    {
        return $this->center;
    }

    /**
     * Set center
     *
     * @param Center
     * @return User
     */
    public function setCenter(Center $center)
    {
        $this->center = $center;

        return $this;
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set groups
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $groups
     * @return User
     */
    public function setGroups(ArrayCollection $groups)
    {
        $this->groups = $groups;

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
     * Set entry
     *
     * @param \DateTime $entry
     * @return User
     */
    public function setEntry(\DateTime $entry)
    {
        $this->entry = $entry;

        return $this;
    }

    /**
     * Get dorm
     *
     * @return Dorm
     */
    public function getDorm()
    {
        return $this->dorm;
    }

    /**
     * Set dorm
     *
     * @param Dorm
     * @return User
     */
    public function setDorm(Dorm $dorm)
    {
        $this->dorm = $dorm;

        return $this;
    }
}