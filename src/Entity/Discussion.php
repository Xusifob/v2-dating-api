<?php


namespace App\Entity;


class Discussion extends Entity implements \JsonSerializable
{


    /**
     * @var Profile
     */
    protected $profile;



    /**
     * @var Message[]
     */
    protected $messages = array();


    /**
     * @var \DateTime;
     */
    protected $createdDate;


    /**
     * @var string
     */
    protected $appId;


    /**
     * @var string
     */
    protected $app;


    /**
     * @return Profile
     */
    public function getProfile(): Profile
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
     * @return \DateTime
     */
    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdDate;
    }

    /**
     * @param $createdDate
     * @throws \Exception
     */
    public function setCreatedDate($createdDate): void
    {
        if(!$createdDate instanceof \DateTime) {
            try {
                $createdDate = new \DateTime($createdDate);
            }catch (\Exception $e) {
                $createdDate = new \DateTime();
            }
        }
        $this->createdDate = $createdDate;
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    public function addMessage(Message $message)
    {
        $this->messages[] = $message;
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
            'profile' => $this->getProfile(),
            'createdDate' => $this->getCreatedDate()->format(\DateTime::ISO8601),
            'messages' => $this->getMessages(),
            'app' => $this->getApp(),
        );
    }


}