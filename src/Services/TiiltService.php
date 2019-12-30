<?php

namespace App\Services;

use App\Entity\Match;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\AbstractNode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Security;


/**
 * Class BumbleService
 *
 * @package App\Services
 */
class TiiltService extends APIService
{

    /**
     * @var Client
     */
    protected $client;


    const APP = 'tiilt';


    const TOKEN = 'tiilt_refresh_token';


    /**
     * BumbleService constructor.
     *
     * @param Security $security
     * @param EntityManagerInterface $em
     */
    public function __construct(Security $security,EntityManagerInterface $em)
    {
        parent::__construct($security,$em);


        $this->client = new Client(array(
            'base_uri' => 'https://www.tiilt.fr/api/',
            'headers' => $this->headers,
        ));

        $this->setUser($security->getUser());

    }


    /**
     * @return Profile[]
     */
    function getMatches() : array
    {

        $data =  $this->rawPost('/search/run');

        try {
            $dom = new Dom();

            $dom->load($data);

            $profiles = array();


            /** @var AbstractNode $node */
            foreach ($dom->find('.ucard') as $node) {
                $profile = new Profile();
                $profile->setApp(self::APP);

                $d = trim($node->find('.uname')->text);
                if (!$d) {
                    continue;
                }

                $n = explode(',', $d);
                $profile->setFullName(trim($n[0]));
                $profile->setAppId($node->getAttribute('data-userid'));
                $profile->setAge((int)trim($n[1]));
                $profile->setDistance($node->find('.location')->text);

                $profile->addPicture($node->find('.photo')->getAttribute('src'));

                $profiles[] = $profile;

            }

            return $profiles;

        }catch (\Exception $e) {
            throw new HttpException(500,$e->getMessage());
        }
    }


    /**
     * @param Profile $profile
     * @return Match
     */
    function like(Profile $profile) : Match
    {

        $body = array(
            'tUserId' => $profile->getAppId(),
            'way' => 'add'
        );

        $this->rawPost('/profile/wink',$body);

        $match = new Match();

        $match->setAction('like');
        $match->setProfile($profile);
        $match->setMatched(false);

        return $match;
    }

    /**
     * @param Profile $profile
     * @return Match
     */
    function pass(Profile $profile) : Match
    {

        $body = array(
            'tUserId' => $profile->getAppId(),
            'way' => 'del'
        );

        $match = new Match();

        $match->setAction('dislike');
        $match->setProfile($profile);
        $match->setMatched(false);

        return $match;

    }


    /**
     * @param array $credentials
     * @return bool
     */
    function login(array $credentials = array()): bool
    {
        $this->parseRequiredArguments($credentials,array('username','password'));


        $body = array(
            'uname' => $credentials['username'],
            'pwd' => $credentials['password'],
        );

        $this->cookieJar = new CookieJar();

        // Init the session
        $data = $this->post('landing/login',$body);

        if(!$data['success']) {
            throw new BadRequestHttpException($data['errMsgs']['uname']);
        }

        if($data['success']) {
            $this->user->setTiiltRefreshToken($data['token']);

            $this->em->persist($this->user);
            $this->em->flush();
            return true;
        }

        return true;
    }


    /**
     * @param array $credentials
     * @return bool
     */
    function validateLogin(array $credentials = array()): bool
    {
        throw new BadRequestHttpException("Validate login is not required on this app " . self::APP);
    }


    /**
     * @param User $user
     */
    public function setUser(User $user)
    {

        $this->user = $user;
        $this->cookieJar = CookieJar::fromArray(array(
            'session' => $this->user->get(self::TOKEN),
        ),'tiilt.com');
    }

    /**
     * @return User
     */
    public function disconnect() : User
    {
        $this->user->setTiiltRefreshToken(null);

        $this->em->persist($this->user);
        $this->em->flush();

        $this->setUser($this->user);

        return $this->user;
    }


    /**
     * @return bool
     */
    public function isConfigured(): bool
    {

        return $this->user->getTiiltRefreshToken() !== null;

    }

}