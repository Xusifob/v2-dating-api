<?php


namespace App\Services;

use App\Entity\Discussion;
use App\Entity\Match;
use App\Entity\Message;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;

class TinderService extends APIService
{

    public $refresh_token;


    const APP = 'tinder';

    const REFRESH_TOKEN = 'tinder_refresh_token';

    const TOKEN = 'tinder_token';


    /**
     * TinderService constructor.
     * @param Security $security
     * @param EntityManager $em
     */
    public function __construct(Security $security,EntityManagerInterface $em)
    {

        parent::__construct($security,$em);

        $this->client = new Client(array(
            'base_uri' => 'https://api.gotinder.com/v2/',
            'headers' => $this->headers,
        ));

        $this->setUser($security->getUser());

    }


    /**
     * @param User $user
     */
    public function setUser(User $user = null)
    {

        $this->user = $user;
        if($this->user) {
            $this->headers = array_merge($this->headers,array(
                'X-Auth-Token' => $this->user->getTinderToken()
            ));
        } else {
            unset($this->headers['X-Auth-Token']);
        }
    }


    /**
     * @return Profile[]
     * @throws \Exception
     */
    public function getMatches() : array
    {

        try {
            $matches = $this->get('recs/core');
        }catch (RequestException $exception) {

            if($exception->getResponse()->getStatusCode() === Response::HTTP_UNAUTHORIZED) {

                $this->refreshToken();

                $matches = $this->get('recs/core');
            }
        }

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
                ->setDistance($match['distance_mi'] . 'km')
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
     * @return Match
     */
    public function like(Profile $profile) : Match
    {

        $match = new Match();

        $result = $this->get('/like/' . $profile->getAppId() . '?s_number=' . $profile->getAttribute('s_number'));


        if($result['likes_remaining'] === 0) {
            throw new AccessDeniedHttpException("Vous n'avez plus de like disponible");
        }

        $match = new Match();

        $match->setProfile($profile);
        $match->setAction('like');
        $match->setMatched($result['match']);

        return $match;

    }

    /**
     * @param Profile $profile
     *
     * @return Match
     */
    public function pass(Profile $profile) : Match
    {
        $result =  $this->get('/pass/' . $profile->getAppId() . '?s_number=' . $profile->getAttribute('s_number'));

        $match = new Match();
        $match->setProfile($profile);
        $match->setAction('dislike');
        $match->setMatched(false);

        return $match;

    }

    /**
     * @param Profile $profile
     *
     * @return Match
     *
     * @throws \Exception
     */
    public function superLike(Profile $profile) : Match
    {
        $result = $this->post('/like/' . $profile->getAppId(). '/super',array('s_number' => $profile->getAttribute('s_number')));

        $match = new Match();

        $match->setProfile($profile);
        $match->setAction('like');
        $match->setMatched(isset($result['match']) ? $result['match'] : false);

        if($result['super_likes']['resets_at']) {

            $nextAction = new \DateTime($result['super_likes']['resets_at']);
            $match->setNextAction($nextAction);
        }

        return $match;

    }


    /**
     * @param array $credentials
     * @return bool
     */
    public function login(array $credentials = array()): bool
    {

        $this->parseRequiredArguments($credentials,array('phone'));

        $number = $credentials['phone'];

        $number = str_replace(array('+','-',' '),'',$number);

        try {
            $data = $this->post('auth/sms/send?auth_type=sms&locale=fr', array(
                'phone_number' => $number,
            ));


            if (isset($data['data']['sms_sent'])) {
                return $data['data']['sms_sent'];
            }

        }catch (RequestException $exception) {

            $body = json_decode($exception->getResponse()->getBody()->getContents(),true);


            if(isset($body['error']['message'])) {
                $message = $body['error']['message'];
            } else {
                $message = "Numéro incorrect";
            }

            throw new BadRequestHttpException($message);
        }

        return false;
    }


    /**
     * @param array $credentials
     *
     * @return bool
     */
    public function validateLogin(array $credentials = array()): bool
    {


        $this->parseRequiredArguments($credentials,array('phone','code'));

        $number = $credentials['phone'];

        $number = str_replace(array('+','-',' '),'',$number);

        try {
           $result = $this->post('auth/sms/validate?auth_type=sms&locale=fr', array(
                'phone_number' => $number,
                'otp_code' => $credentials['code'],
                'is_update' => false
            ));
            $token = $result['data']['refresh_token'];

            $this->user->setTinderRefreshToken($token);
            $this->user->setPhone($number);
            $this->em->persist($this->user);
            $this->em->flush();

            $this->refreshToken();

            $this->fetchProfileInfos();

            return true;

        }catch (RequestException $exception) {

            $body = json_decode($exception->getResponse()->getBody()->getContents(),true);

            if(isset($body['error']['message'])) {
                $message = $body['error']['message'];
            } else {
                $message = 'An error Occured';
            }

            throw new BadRequestHttpException($message);
        }

    }

    /**
     * @return User
     */
    public function refreshToken() : User
    {
        $response = $this->post('auth/login/sms?locale=fr',array(
            'refresh_token' => $this->user->getTinderRefreshToken(),
            'phone_number' => $this->user->getPhone(),
        ));


        if(!$response['data']['api_token']) {
            throw new AccessDeniedException(json_encode($response));
        }

        $this->user->setTinderToken($response['data']['api_token']);

        $this->em->persist($this->user);
        $this->em->flush();

        $this->setUser($this->user);

        return $this->user;
    }


    /**
     * @return User
     */
    public function disconnect() : User
    {
        $this->user->setTinderToken(null);
        $this->user->setTinderRefreshToken(null);

        $this->em->persist($this->user);
        $this->em->flush();

        $this->setUser($this->user);

        return $this->user;
    }


    /**
     *
     */
    public function getMessageList()
    {

        $result =  $this->get('matches?count=30&is_tinder_u=false&locale=fr&message=1');

        $discussions = array();

        foreach ($result['data']['matches'] as $match) {


            if($match['closed'] || $match['dead']) {
                continue;
            }

            $discussion = new Discussion();
            $discussion->setAppId($match['id']);
            $discussion->setCreatedDate($match['created_date']);
            $discussion->setApp(self::APP);


            $profile = new Profile();
            $profile->setAppId($match['person']['_id']);
            $profile->setApp(self::APP);
            $profile->setFullName($match['person']['name']);
            if(isset($match['person']['bio'])) {
                $profile->setBio($match['person']['bio']);
            }
            $age = new \DateTime($match['person']['birth_date']);
            $years = $age->diff(new \DateTime())->y;
            $profile->setAge($years);

            foreach ($match['person']['photos'] as $photo) {
                $profile->addPicture($photo['processedFiles'][1]['url']);
            }

            $discussion->setProfile($profile);

            $u = $this->getProfile();

            foreach ($match['messages'] as $m) {

                $message = new Message();
                $message->setAppId($m['_id']);
                $message->setContent($m['message']);
                $message->setSentDate($m['sent_date']);
                $message->setApp(self::APP);

                if ($profile->getAppId() == $m['from']) {
                    $message->setProfile($profile);
                } else {
                    $message->setProfile($u);
                }

                $discussion->addMessage($message);

            }

            $discussions[] = $discussion;

        }

        return $discussions;
    }


    /**
     * @param string $discussion_id
     * @return Message[]
     * @throws \Exception
     */
    public function getDiscussion(string $discussion_id ) : array
    {
        $data = $this->get('matches/'. $discussion_id .'/messages?count=30&locale=fr');

        $messages = array();

        foreach ($data['data']['messages'] as $m) {
            $message = new Message();
            $message->setSentDate($m['sent_date']);
            $message->setAppId($m['_id']);
            $message->setContent($m['message']);
            $message->setApp(self::APP);

            $profile = new Profile();
            $profile->setAppId($m['from']);
            $profile->setApp(self::APP);
            $message->setProfile($profile);

            $messages[] = $message;
        }


        return $messages;

    }



    /**
     * @param $discussionId
     * @param Message $message
     * @return Message
     * @throws \Exception
     */
    public function sendMessage($discussionId, Message $message) : Message
    {

        $body = array(
            "message" => $message->getContent(),
        );


        $d = $this->post('https://api.gotinder.com/user/matches/'. $discussionId .'?locale=fr',$body);


        $message->setSentDate($d['sent_date']);
        $message->setApp(self::APP);
        $message->setAppId($d['_id']);
        $message->setProfile($this->getProfile());


        return $message;


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



    public function fetchProfileInfos() : User
    {

        $data = $this->get('profile?include=user');

        $this->user->setFullName($data['data']['user']['name']);
        $this->user->setPhoto($data['data']['user']['photos'][0]['processedFiles'][1]['url']);

        $this->em->persist($this->user);
        $this->em->flush();

        return $this->user;

    }

    /**
     * @return bool
     */
    public function isConfigured(): bool
    {

        return $this->user->getTinderRefreshToken() !== null;
    }


}