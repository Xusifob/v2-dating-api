<?php


namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends Entity implements UserInterface, \JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    protected $mail;


    /**
     * @ORM\Column(type="string", length=500)
     */
    protected $password;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $settings = array();

    /**
     *
     * Phone Number
     *
     * @ORM\Column(type="string", length=20,nullable=true)
     */
    protected $phone;


    /**
     * @var string
     * @ORM\Column(type="string", length=256,nullable=true)
     */
    protected $photo;

    /**
     * @var string
     * @ORM\Column(type="string", length=64,nullable=true)
     */
    protected $fullName;

    /**
     * @ORM\Column(type="string", length=128,nullable=true)
     */
    protected $tinder_refresh_token;

    /**
     * @ORM\Column(type="string",length=128, nullable=true)
     */
    protected $tinder_token;

    /**
     * @ORM\Column(type="string", length=128,nullable=true)
     */
    protected $tiilt_refresh_token;

    /**
     * @ORM\Column(type="string", length=128,nullable=true)
     */
    protected $okcupid_token;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $next_super_like;


    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64,nullable=true)
     */
    protected $bumble_user_id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64,nullable=true)
     */
    protected $badoo_user_id;



    public function getSalt()
    {
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return mixed
     */
    public function getMail() : string
    {
        return $this->mail;
    }

    /**
     * @param mixed $mail
     */
    public function setMail($mail): void
    {
        $this->mail = $mail;
    }

    public function getUsername()
    {
        return $this->mail;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getTinderRefreshToken()
    {
        return $this->tinder_refresh_token;
    }

    /**
     * @param mixed $tinder_refresh_token
     */
    public function setTinderRefreshToken($tinder_refresh_token): void
    {
        $this->tinder_refresh_token = $tinder_refresh_token;
    }


    /**
     * @return mixed
     */
    public function getTinderToken()
    {
        return $this->tinder_token;
    }

    /**
     * @param mixed $tinder_token
     */
    public function setTinderToken($tinder_token): void
    {
        $this->tinder_token = $tinder_token;
    }

    /**
     * @return mixed
     */
    public function getTiiltRefreshToken()
    {
        return $this->tiilt_refresh_token;
    }

    /**
     * @param mixed $tiilt_refresh_token
     */
    public function setTiiltRefreshToken($tiilt_refresh_token): void
    {
        $this->tiilt_refresh_token = $tiilt_refresh_token;
    }

    /**
     * @return array
     */
    public function getSettings(): ?array
    {
        return $this->settings ? $this->settings : array();
    }

    /**
     * @param array $settings
     */
    public function setSettings(?array $settings): void
    {
        if(is_array($settings)) {
            $this->settings = $settings;
        }
    }

    /**
     * @return string
     */
    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    /**
     * @param string $photo
     */
    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
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
     */
    public function setFullName(?string $fullName): void
    {
        $this->fullName = $fullName;
    }

    /**
     * @return mixed
     */
    public function getNextSuperLike()
    {
        return $this->next_super_like;
    }

    /**
     * @param mixed $next_super_like
     */
    public function setNextSuperLike($next_super_like): void
    {
        $this->next_super_like = $next_super_like;
    }

    /**
     * @return string
     */
    public function getBumbleUserId(): ?string
    {
        return $this->bumble_user_id;
    }

    /**
     * @param string $bumble_user_id
     */
    public function setBumbleUserId(?string $bumble_user_id): void
    {
        $this->bumble_user_id = $bumble_user_id;
    }

    /**
     * @return string
     */
    public function getBadooUserId(): ?string
    {
        return $this->badoo_user_id;
    }

    /**
     * @param string $badoo_user_id
     */
    public function setBadooUserId(?string $badoo_user_id): void
    {
        $this->badoo_user_id = $badoo_user_id;
    }






    public function eraseCredentials()
    {
    }

    /**
     * @return mixed
     */
    public function getOkcupidToken() : ?string
    {
        return $this->okcupid_token;
    }

    /**
     * @param mixed $okcupid_token
     */
    public function setOkcupidToken(?string  $okcupid_token): void
    {
        $this->okcupid_token = $okcupid_token;
    }




    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'mail' => $this->getMail(),
            'phone' => $this->getPhone(),
            'settings' => $this->getSettings(),
            'photo' => $this->getPhoto(),
            'fullName' => $this->getFullName(),
        );
    }

}