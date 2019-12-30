<?php


namespace App\Services;

use App\Entity\Match;
use App\Entity\Profile;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;

abstract class OkCupidService extends APIService
{


    /**
     * @var CookieJar
     */
    public $cookieJar;


    /**
     * @var string
     */
    public $token;


    const APP = 'okcupid';


    /**
     * OkCupidService constructor.
     * @param Security $security
     */
    public function __construct(Security $security,EntityManagerInterface $em)
    {

        parent::__construct($security,$em);

        $this->headers = array(
            'x-okcupid-platform' => 'DESKTOP',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.143 Safari/537.36',
        );

        $this->client = new Client(array(
            'base_uri' => 'https://www.okcupid.com/1/apitun/',
            'headers' => $this->headers,
            'cookies' => $this->cookieJar,
        ));
    }


    /**
     * @param array $credentials
     * @return bool
     */
    public function login(array $credentials = array()) : bool
    {

        $response =  json_decode($this->client->post('/login',array(
                'cookies' => $this->cookieJar,
                'form_params' => array(
                    'okc_api' => 1,
                    'username' => $credentials['okcupid_username'],
                    'password' => $credentials['okcupid_password'],
                )
            )
        )->getBody()->getContents(),true);


        // Set token
        $this->headers['Authorization'] = "Bearer " . $response['oauth_accesstoken'];

    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getMatches() : array
    {


        $match = $this->get('/quickmatch?okc_api=1');

        $profil = $this->rawGet('/profile/' . $match['sn'] . '');

      //  $profil = str_replace('random','',$profil);

        echo $profil;
        die();

        dump($match);
        dump($profil);

        die();

        $profiles = array();

        foreach ($matches['data']['results'] as $match)
        {

            $age = new \DateTime($match['user']['birth_date']);
            $years = $age->diff(new \DateTime())->y;

            $profile = new Profile();
            $profile
                ->setFullName($match['user']['name'])
                ->setBio($match['user']['bio'])
                ->setApp(self::APP)
                ->setAppId($match['user']['_id'])
                ->setDistance($match['distance_mi'])
                ->setIsFavorite(false)
                ->setAge($years)
                ->setAttribute('s_number',$match['s_number']);
            ;

            foreach($match['user']['photos'] as $photo) {
                $profile->addPicture($photo['processedFiles'][1]['url']);
            }

            if(isset($match['user']['schools'][0]['name'])) {
                $profile->setSchool($match['user']['schools'][0]['name']);
            }
            if(isset($match['user']['jobs'][0]['title']['name'])) {
                $profile->setJobTitle($match['user']['jobs'][0]['title']['name']);
            }


            $profiles[] = $profile;

        }

        return $profiles;

    }


    /**
     * @param Profile $profile
     *
     * @return array
     */
    public function like(Profile $profile) : Match
    {
        return $this->get('/like/' . $profile->getAppId() . '?s_number=' . $profile->getAttribute('s_number'));
    }

    /**
     * @param Profile $profile
     *
     * @return Match
     */
    public function pass(Profile $profile) : Match
    {
        return $this->get('/pass/' . $profile->getAppId() . '?s_number=' . $profile->getAttribute('s_number'));
    }



    /**
     * @return bool
     */
    public function isConfigured(): bool
    {

        return false;

    }


}