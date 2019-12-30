<?php


namespace App\Entity;


class Message extends Entity implements \JsonSerializable
{


    /**
     * @var Profile
     */
    protected $profile;



    /**
     * @var $content
     */
    protected $content = '';


    /**
     * @var string
     */
    protected $appId;


    /**
     * @var string
     */
    protected $sentDate;


    /**
     * @var string
     */
    protected $app;


    /**
     * @return Profile
     */
    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    /**
     * @param Profile $profile
     */
    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * @return mixed
     */
    public function getSentDate() : \DateTime
    {
        return $this->sentDate;
    }


    /**
     * @param $sentDate
     * @throws \Exception
     */
    public function setSentDate($sentDate): void
    {
        if(!$sentDate instanceof \DateTime) {
            try {
                $sentDate = new \DateTime($sentDate);
            }catch (\Exception $e) {
                $sentDate = new \DateTime();
            }
        }
        $this->sentDate = $sentDate;
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
    public function setApp(string $app): void
    {
        $this->app = $app;
    }



    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return array(
            'appId' => $this->getAppId(),
            'content' => $this->getContent(),
            'profile' => $this->getProfile()->toArray('small'),
            'sentDate' => $this->getSentDate()->format(\DateTime::ISO8601),
            'app' => $this->getApp(),
        );
    }


}