<?php

namespace App\Services;

use App\Entity\Match;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;


/**
 * Class APIService
 * @package App\Services
 */
abstract class APIService
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var CookieJar
     */
    protected $cookieJar;

    /**
     * @var Security
     */
    protected $security;

    /**
     * @var User
     */
    protected $user;


    /**
     * @var EntityManagerInterface
     */
    protected $em;


    /**
     * APIService constructor.
     * @param Security $security
     * @param EntityManagerInterface $em
     */
    public function __construct(Security $security,EntityManagerInterface $em)
    {

        $this->em = $em;

        $this->security = $security;

        $this->user = $security->getUser();

        $this->headers = array(
            'Accept'=> 'application/json',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
        );

        $this->cookieJar = new CookieJar();


    }


    /**
     * @param $url
     *
     * @return mixed
     */
    public function get($url)
    {

        return (json_decode($this->rawGet($url),true));

    }

    /**
     * @param $url
     * @return mixed
     */
    public function rawGet($url)
    {
        return $this->rawGetFull($url)->getBody()->getContents();
    }


    /**
     * @param $url
     * @return mixed
     */
    public function rawGetFull($url)
    {

        return $this->client->get($url,array(
            'headers' => $this->headers,
            'cookies' => $this->cookieJar,
        ));

    }

    /**
     * @param $url
     * @param array $json
     * @param array $form_params
     * @return mixed
     */
    protected function post($url,$json = array(),$form_params = array())
    {

        return json_decode($this->rawPost($url,$json,$form_params),true);

    }

    /**
     * @param $url
     * @param array $json
     * @param array $form_params
     * @return mixed
     */
    protected function rawPost($url,$json = array(),$form_params = array())
    {

        return ($this->rawPostFull($url,$json,$form_params)->getBody()->getContents());

    }



    /**
     * @param $url
     * @param array $json
     * @param array $form_params
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function rawPostFull($url,$json = array(),$form_params = array())
    {

        if(is_string($json)) {
            $json = json_decode($json, true);
        }


        return $this->client->post($url,array(
            'json' => $json,
            'form_params' => $form_params,
            'headers' => $this->headers,
            'cookies' => $this->cookieJar,
        ));
    }


    /**
     * @param $url
     * @param $data
     * @return mixed
     */
    protected function put($url,$data)
    {

        return json_decode($this->client->put($url,array(
            'json' => $data,
            'headers' => $this->headers,
            'cookies' => $this->cookieJar,
        ))->getBody()->getContents(),true);

    }


    /**
     * @param array $args
     * @param array $required
     * @return bool
     */
    protected function parseRequiredArguments($args = array(),$required = array()) : bool
    {
        foreach ($required as $req) {
            if(!isset($args[$req])) {
                throw new BadRequestHttpException(sprintf("Required attribute '%s' is missing",$req));

            }
        }

        return  true;
    }


    /**
     * @return Profile[]
     */
    abstract function getMatches() : array;


    /**
     * @param Profile $profile
     * @return Match
     */
    abstract function like(Profile $profile) : Match;

    /**
     * @param Profile $profile
     * @return Match
     */
    abstract function pass(Profile $profile) : Match;


    /**
     * @param array $credentials
     * @return bool
     */
    abstract function login(array $credentials = array()) : bool;

    /**
     *
     * 2nd step of login (handle OTP text)
     *
     * @param array $credentials
     * @return bool
     */
    abstract function validateLogin(array $credentials = array()) : bool;

    /**
     * @param Profile $profile
     *
     * @return Match
     */
    public function superLike(Profile $profile) : Match
    {
        throw new BadRequestHttpException("Super like is not enabled in the app " .$this::APP);

    }


    /**
     * @param $lat
     * @param $long
     * @return bool
     */
    public function updateLocation($lat,$long) : bool
    {
        throw new BadRequestHttpException("Update Location is not enabled in the app " .$this::APP);

    }


    /**
     * @param User $user
     * @return bool
     */
    abstract function disconnect() : User;



    /**
     * @return bool
     */
    abstract function isConfigured() : bool;

    /**
     * @return CookieJar
     */
    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar;
    }


    /**
     * @return string
     */
    public function getDomain() : string
    {
        return ($this->client->getConfig('base_uri')->getHost());
    }

    /**
     * @param array $array
     */
    public function setCookieJar(array $array = array()): void
    {


        if(!$array) {
            return;
        }

        $this->cookieJar = new CookieJar();

        foreach ($array as $key => $value) {
            $setCookie = new SetCookie($value);
            $this->cookieJar->setCookie($setCookie);
        }

    }


    /**
     * @return Profile
     */
    public function getProfile() : Profile
    {
        $u = new Profile();
        $u->setFullName($this->user->getFullName());
        $u->addPicture($this->user->getPhoto());

        return $u;
    }



    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

}