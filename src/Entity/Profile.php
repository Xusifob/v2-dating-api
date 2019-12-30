<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Table(name="profile")
 * @ORM\Entity
 */
class Profile extends Entity implements \JsonSerializable
{


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid")
     */
    protected $id;


    /**
     * @var string
     * @ORM\Column(type="string", length=25)
     */
    protected $fullName;

    /**
     * @var string
     * @ORM\Column(type="string", length=25)
     */
    protected $app;


    /**
     * @var string
     * @ORM\Column(type="string", length=1024,nullable=true)
     */
    protected $bio;

    /**
     * @var string
     * @ORM\Column(type="string", length=64,nullable=true)
     */
    protected $jobTitle;

    /**
     * @var int
     * @ORM\Column(type="smallint",nullable=true)
     */
    protected $age;


    /**
     * @var string
     * @ORM\Column(type="string", length=64,nullable=true)
     */
    protected $distance;


    /**
     * @var string
     * @ORM\Column(type="string", length=64,nullable=true)
     */
    protected $school;


    /**
     * @var array
     * @ORM\Column(type="array",nullable=true)
     */
    protected $pictures = array();

    /**
     * @var boolean
     * @ORM\Column(type="boolean",nullable=true)
     */
    protected $isFavorite = false;


    /**
     * @var array
     * @ORM\Column(type="array")
     */
    protected $attributes = array();


    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $appId;


    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected $owner;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }




    /**
     * @return string
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     *
     * @return $this
     */
    public function setFullName(string $fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return string
     */
    public function getApp(): string
    {
        return $this->app;
    }

    /**
     * @param string $app
     */
    public function setApp(string $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * @return string
     */
    public function getBio(): ?string
    {
        return $this->bio;
    }

    /**
     * @param string $bio
     */
    public function setBio(?string $bio)
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * @return string
     */
    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    /**
     * @param string $jobTitle
     */
    public function setJobTitle(?string $jobTitle)
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    /**
     * @return int
     */
    public function getAge(): ?int
    {
        return $this->age;
    }

    /**
     * @param int $age
     */
    public function setAge(int $age)
    {
        $this->age = $age;

        return $this;
    }

    /**
     * @return int
     */
    public function getDistance(): ?string
    {
        return $this->distance;

    }

    /**
     * @param $distance
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @return array
     */
    public function getPictures(): array
    {
        return $this->pictures;
    }

    /**
     * @param array $pictures
     */
    public function setPictures(array $pictures)
    {
        $this->pictures = $pictures;

        return $this;
    }


    /**
     * @param string $picture
     * @return $this
     */
    public function addPicture(string $picture)
    {
        $this->pictures[] = $picture;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFavorite(): ?bool
    {
        return $this->isFavorite;
    }

    /**
     * @param bool $isFavorite
     */
    public function setIsFavorite(bool $isFavorite)
    {
        $this->isFavorite = $isFavorite;

        return $this;
    }

    /**
     * @return string
     */
    public function getAppId(): ?string
    {
        return $this->appId;
    }


    /**
     * @param string $appId
     * @return $this
     */
    public function setAppId(string $appId)
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSchool(): ?string
    {
        return $this->school;
    }

    /**
     * @param string $school
     *
     * @return  $this
     */
    public function setSchool(?string $school)
    {
        $this->school = $school;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }


    /**
     * @param $key
     * @param $value
     */
    public function setAttribute($key,$value)
    {
        $this->attributes[$key] = $value;
    }


    /**
     * @param $key
     * @return mixed|null
     */
    public function getAttribute($key)
    {
        if(isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return null;
    }

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     */
    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }


    /**
     * @param string $group
     * @return array
     */
    public function toArray($group = 'small')
    {
        return array(
            'appId' => $this->getAppId(),
            'fullName' => $this->getFullName(),
            'photo' => isset($this->getPictures()[0]) ? $this->getPictures()[0] : ''
        );
    }


    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'app' => $this->getApp(),
            'fullName' => $this->getFullName(),
            'bio' => $this->getBio(),
            'age' => $this->getAge(),
            'appId' => $this->getAppId(),
            'isFavorite' => $this->isFavorite(),
            'pictures' => $this->getPictures(),
            'distance' => $this->getDistance(),
            'jobTitle' => $this->getJobTitle(),
            'school' => $this->getSchool(),
            'attributes' => $this->getAttributes(),
        );
    }
}