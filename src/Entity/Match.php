<?php


namespace App\Entity;


class Match extends Entity implements \JsonSerializable
{


    /**
     * @var Profile
     */
    public $profile;


    /**
     * @var string
     */
    public $action;


    /**
     * @var bool
     */
    public $matched;


    /**
     * @var \DateTime
     */
    public $nextAction;

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
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }



    /**
     * @return \DateTime
     */
    public function getNextAction(): ?\DateTime
    {
        return $this->nextAction;
    }

    /**
     * @param \DateTime $nextAction
     */
    public function setNextAction(\DateTime $nextAction): void
    {
        $this->nextAction = $nextAction;
    }

    /**
     * @return bool
     */
    public function isMatched(): bool
    {
        return $this->matched;
    }

    /**
     * @param bool $matched
     */
    public function setMatched(bool $matched): void
    {
        $this->matched = $matched;
    }





    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return array(
            'action' => $this->getAction(),
            'nextAction' => $this->getNextAction(),
            'profile' => $this->getProfile(),
            'matched' => $this->isMatched(),
        );
    }


}