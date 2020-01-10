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
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Security;


/**
 * Class BumbleService
 *
 * @package App\Services
 */
class BumbleService extends APIService
{

    /**
     * @var Client
     */
    protected $client;


    const APP = 'bumble';



    /**
     * BumbleService constructor.
     *
     * @param Security $security
     * @param EntityManagerInterface $em
     */
    public function __construct(Security $security,EntityManagerInterface $em)
    {
        parent::__construct($security,$em);

        $this->headers['x-use-session-cookie'] = 1;
        $this->headers['Origin'] = 'https://bumble.com';
        $this->headers['Host'] = 'bumble.com';
        $this->headers['Referer'] = 'https://bumble.com/get-started';
        $this->headers['Content-Type'] = 'json';
        $this->headers['X-Desktop-web'] = 1;

        $stack = HandlerStack::create();
        $stack->push(EffectiveMiddlewareService::middleware());

        $this->client = new Client(array(
            'base_uri' => 'https://bumble.com/',
            'headers' => $this->headers,
            'handler' => $stack
        ));

        $this->setUser($security->getUser());

    }


    /**
     * @return Profile[]
     */
    function getMatches() : array
    {

        $body = '{"body":[{"message_type":81,"server_get_encounters":{"number":10,"context":1,"user_field_filter":{"projection":[210,370,200,230,490,540,530,560,291,732,890,930,662,570,380,493,1140,1150,1160,1161],"request_albums":[{"album_type":7},{"album_type":12,"external_provider":12,"count":8}],"game_mode":0,"request_music_services":{"top_artists_limit":8,"supported_services":[29],"preview_image_size":{"width":120,"height":120}}}}}],"message_id":17,"message_type":81,"version":1,"is_background":false}';
        $data =  $this->post('/mwebapi.phtml?SERVER_GET_ENCOUNTERS',$body);

        $profiles = array();

        $this->handleServerError($data);

        if(!isset($data['body'][0]['client_encounters']['results'])) {
            return array();
        }

        foreach ($data['body'][0]['client_encounters']['results'] as $object)
        {
            $user = $object['user'];
            $profile = new Profile();
            $profile->setAge($user['age']);
            $profile->setAppId($user['user_id']);
            $profile->setFullName($user['name']);
            if(isset($user['distance_short'])) {
                $profile->setDistance($user['distance_short']);
            }

            foreach ($user['profile_fields'] as $field) {
                switch ($field['id']) {
                    case 'location':
                        $profile->setDistance($field['display_value']);
                        break;
                    case 'aboutme_text' :
                        $profile->setBio($field['display_value']);
                        break;
                    default:
                        $profile->addProfileField($field['name'],$field['display_value']);
                        break;
                }
            }

            foreach ($user['albums'][0]['photos'] as $photo) {
                $profile->addPicture('https:' . $photo['large_url']);
            }

            $profile->setApp(self::APP);
            $profiles[] = $profile;
        }

        return $profiles;
    }




    /**
     * @return Profile[]
     */
    function getPendingMatches() : array
    {

        $body = '{"body":[{"message_type":245,"server_get_user_list":{"filter":[8],"filter_match_mode":[0],"folder_id":6,"user_field_filter":{"projection":[210,662,670,200,890,230,490,340,291,763]},"preferred_count":21}}],"message_id":16,"message_type":245,"version":1,"is_background":false}';

        $data =  $this->post('/mwebapi.phtml?SERVER_GET_USER_LIST',$body);


        $profiles = array();

        $this->handleServerError($data);

        if(!isset($data['body'][0]['client_user_list']['section'])) {
            return array();
        }

        foreach ($data['body'][0]['client_user_list']['section'] as $object)
        {

            if(!isset($object['users'])) {
                continue;
            }
            $users = $object['users'];

            foreach ($users as $user) {

                $profile = new Profile();
                $profile->setAppId($user['user_id']);

                $profile->addPicture('https:' . $user['profile_photo']['large_url']);

                $profile->setApp(self::APP);
                $profiles[] = $profile;
            }
        }

        return $profiles;
    }


    /**
     * @param Profile $profile
     * @return array
     */
    function like(Profile $profile) : Match
    {
        $body = json_decode('{"body":[{"message_type":80,"server_encounters_vote":{"person_id":"'. $profile->getAppId() .'","vote":2,"vote_source":1,"game_mode":0}}],"message_id":26,"message_type":80,"version":1,"is_background":false}',true);

        $data = $this->post('/mwebapi.phtml?SERVER_ENCOUNTERS_VOTE',$body);

        $this->handleServerError($data);

        $match = new Match();

        $m = false;
        if(isset($data['body'][0]['client_vote_response']['vote_response_type'])) {
            if($data['body'][0]['client_vote_response']['vote_response_type'] == 3) {
                $m = true;
            }
        }

        $match->setAction('like');
        $match->setProfile($profile);
        $match->setMatched($m);

        return $match;
    }

    /**
     * @param Profile $profile
     * @return Match
     */
    function pass(Profile $profile) : Match
    {

        $body = '{"body":[{"message_type":80,"server_encounters_vote":{"person_id":"'. $profile->getAppId() .'","vote":3,"vote_source":1,"game_mode":0}}],"message_id":26,"message_type":80,"version":1,"is_background":false}';

        $data =  $this->post('/mwebapi.phtml?SERVER_ENCOUNTERS_VOTE',$body);

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
        $this->parseRequiredArguments($credentials,array('prefix','phone'));

        $number = $credentials['phone'];
        $prefix = $credentials['prefix'];

        $number = str_replace(array('+','-',' '),'',$number);
        $prefix = str_replace(array('+','-',' '),'',$prefix);


        $this->cookieJar = new CookieJar();

        //  throw new BadRequestHttpException("Bumble can't be configured yet");


        // Init app
        $body = '{"version":1,"message_type":2,"message_id":1,"body":[{"message_type":2,"server_app_startup":{"app_build":"MoxieWebapp","app_name":"moxie","app_version":"1.0.0","can_send_sms":false,"user_agent":"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36","screen_width":1680,"screen_height":1050,"language":0,"is_cold_start":true,"external_provider_redirect_url":"https://bumble.com/static/external-auth-result.html?","locale":"en-us","system_locale":"fr-FR","app_platform_type":5,"app_product_type":400,"device_info":{"webcam_available":true,"form_factor":3},"build_configuration":2,"supported_features":[141,145,11,15,1,2,13,46,4,248,6,18,155,70,160,58,140,130,189,187,220,223,100,180,197,161,232,29,227,237,239,254,190],"supported_minor_features":[472,317,2,216,244,232,19,130,225,246,31,125,183,114,254,8,9,83,41,427,115,288,420,477,93,226,413,267,39,290,398,453,180,281,40,455,280,499,471,397,411,352,447,146,469,118,63,391,523,293,431,574,405,547,451,571,319,297,558],"supported_notifications":[83,73,3,72,46,109,81],"supported_payment_providers":[26,100,35,100001],"supported_promo_blocks":[{"context":92,"position":13,"types":[71]},{"context":89,"position":5,"types":[160,358]},{"context":8,"position":13,"types":[111,112,113]},{"context":53,"position":18,"types":[136,93,12]},{"context":45,"position":18,"types":[327]},{"context":45,"position":15,"types":[93,134,135,136,137,327,308,309,334]},{"context":10,"position":1,"types":[265,266,286]},{"context":148,"position":21,"types":[179,180,283]},{"context":130,"position":13,"types":[268,267]},{"context":113,"position":1,"types":[228]},{"context":3,"position":1,"types":[80]},{"context":3,"position":4,"types":[80,228]},{"context":119,"position":1,"types":[80,282,81,90]},{"context":43,"position":1,"types":[96,307]},{"context":10,"position":18,"types":[358,174]},{"context":10,"position":8,"types":[358]},{"context":26,"position":16,"types":[286,371]},{"context":10,"position":6,"types":[286,373,372]}],"supported_user_substitutes":[{"context":1,"types":[3]}],"supported_onboarding_types":[9],"user_field_filter_client_login_success":{"projection":[210,220,230,200,91,890,340,10,11,231,71,93,100]},"a_b_testing_settings":{"tests":[{"test_id":"bumble_web_boom_screen_opens_profile_xp"},{"test_id":"bumble__gifs_with_old_input"}]},"dev_features":["bumble_bizz","bumble_snooze","bumble_questions","bumble__pledge","bumble__request_photo_verification","bumble_moves_making_impact_","bumble__photo_verification_filters","bumble_gift_cards","bumble__antighosting_xp_dead_chat_followup","bumble_private_detector"],"device_id":"bf0035d5-35d5-d5ac-ac03-034625a3f106","supported_screens":[{"type":23,"version":2},{"type":26,"version":0},{"type":13,"version":0},{"type":14,"version":0},{"type":15,"version":0},{"type":16,"version":0},{"type":17,"version":0},{"type":18,"version":0},{"type":19,"version":0},{"type":20,"version":0},{"type":21,"version":0},{"type":25,"version":0},{"type":27,"version":0},{"type":28,"version":0},{"type":57,"version":0},{"type":29,"version":1},{"type":69,"version":0},{"type":63,"version":0},{"type":92,"version":0},{"type":64,"version":0},{"type":65,"version":0},{"type":66,"version":0},{"type":67,"version":0}],"supported_landings":[{"source":25,"params":[20,3],"search_settings_types":[3]}]}}],"is_background":false}';
        $data = $this->rawPostFull('/mwebapi.phtml?SERVER_APP_STARTUP',$body);

        $body = '{"version":1,"message_type":640,"message_id":11,"body":[{"message_type":640,"server_validate_phone_number":{"phone_prefix":"+' . $prefix .'","phone":"' . $number .'","context":203}}],"is_background":false}';

        $data = $this->post('/mwebapi.phtml?SERVER_VALIDATE_PHONE_NUMBER',$body);

        if(isset($data['body'][0]['client_validate_user_field']['valid']) && !$data['body'][0]['client_validate_user_field']['valid']) {
            throw new BadRequestHttpException($data['body'][0]['client_validate_user_field']['error_message']);
        }


        $body = '{"version":1,"message_type":678,"message_id":10,"body":[{"message_type":678,"server_submit_phone_number":{"phone_prefix":"+'. $prefix .'","phone":"'. $number .'","context":203,"screen_context":{"screen":23}}}],"is_background":false}';
        $data = $this->post('/mwebapi.phtml?SERVER_SUBMIT_PHONE_NUMBER',$body);

        return true;

    }


    /**
     * @param array $credentials
     * @return bool
     */
    function validateLogin(array $credentials = array()): bool
    {

        $this->parseRequiredArguments($credentials,array('phone','password'));

        $phone = $credentials['phone'];
        $password = $credentials['password'];

        $body = '{"version":1,"message_type":15,"message_id":13,"body":[{"message_type":15,"server_login_by_password":{"remember_me":true,"phone":"'. $phone .'","password":"'. $password .'"}}],"is_background":false}';

        $data = $this->post('/mwebapi.phtml?SERVER_LOGIN_BY_PASSWORD',$body);

        $this->handleServerError($data);

        if(isset($data['body'][0]['form_failure']['errors'][0]['error'])) {
            throw new BadRequestHttpException($data['body'][0]['form_failure']['errors'][0]['error']);
        }

        $user_id = $data['body'][0]['client_login_success']['user_info']['user_id'];

        $this->user->setBumbleUserId($user_id);
        $this->em->persist($this->user);
        $this->em->flush();


        if(isset($data['body'][0]['client_login_success'])) {
            return true;
        }

        return false;

    }


    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        // @Void
    }


    /**
     * @return User
     */
    public function disconnect() : User
    {

        $this->user->setBumbleUserId(null);
        $this->em->persist($this->user);
        $this->em->flush();

        $this->setUser($this->user);

        return $this->user;
    }


    /**
     * @param array $location
     * @return bool
     */
    public function updateLocation($location = array()) : bool
    {

        $this->parseRequiredArguments($location,array('lat','lon'));

        $body = '{"version":1,"message_type":4,"message_id":8,"body":[{"message_type":4,"server_update_location":{"location":[{"longitude":'. $location['lon'] .',"latitude":'. $location['lat'] .'}]}}],"is_background":false}';

        $data = $this->post('/mwebapi.phtml?SERVER_UPDATE_LOCATION',$body);


        $this->parseRequiredArguments($data);

        return true;
    }






    /**
     * @return array
     */
    public function getMessageList() : array
    {

        $body = '{"version":1,"message_type":245,"message_id":5,"body":[{"message_type":245,"server_get_user_list":{"user_field_filter":{"projection":[200,340,230,640,580,300,860,280,590,591,250,700,762,592,880,582,930,585,583,305,330,763,1422,584,1262]},"preferred_count":30,"folder_id":0}}],"is_background":false}';

        $data = $this->post('/mwebapi.phtml?SERVER_GET_USER_LIST',$body);


        $discussions = array();

        $this->handleServerError($data);

        foreach ($data['body'][0]['client_user_list']['section'] as $key => $val) {

            if(!isset($val['users'])) {
                continue;
            }


            foreach ($val['users'] as $k => $user) {

                if ($user['is_deleted'] || $user['is_locked']) {
                    continue;
                }



                $profile = new Profile();
                $profile->setFullName($user['name']);
                $profile->addPicture('https:' . $user['profile_photo']['large_url']);
                $profile->setApp(self::APP);
                $profile->setAppId($user['user_id']);

                $discussion = new Discussion();
                $discussion->setProfile($profile);
                $discussion->setAppId($user['user_id']);
                $discussion->setApp(self::APP);

                $message = new Message();
                $message->setAppId(self::APP);
                $message->setApp(uniqid());
                $message->setContent($this->parseMessage($user['display_message']));

                $discussion->addMessage($message);

                $discussions[] = $discussion;
            }
        }


        return $discussions;

    }


    /**
     * @param $discussionId
     * @param Message $message
     * @return Message
     * @throws \Exception
     */
    public function sendMessage($discussionId, Message $message) : Message
    {

        $body = json_decode('{"version":1,"message_type":104,"message_id":10,"body":[{"message_type":104,"chat_message":{"mssg":"","message_type":1,"uid":"","from_person_id":"","to_person_id":"","read":false}}],"is_background":false}',true);

        $body['body'][0]['chat_message'] = array(
            "mssg" => $message->getContent(),
            "message_type" => 1,
            "uid" => '',
            "from_person_id" => $this->user->getBumbleUserId(),
            "to_person_id" => $discussionId,
            "read" => false,
        );

        $data = $this->post('/mwebapi.phtml?SERVER_SEND_CHAT_MESSAGE',$body);

        $this->handleServerError($data);

        $message->setAppId($data['body'][0]['chat_message_received']['chat_message']['uid']);
        $message->setApp(self::APP);
        $message->setSentDate($data['body'][0]['chat_message_received']['chat_message']['date_modified']);
        $message->setProfile($this->getProfile());

        return $message;

    }


    public function getDiscussion(string $discussion_id) : array
    {

        $body = '{"version":1,"message_type":102,"message_id":28,"body":[{"message_type":102,"server_open_chat":{"user_field_filter":{"projection":[200,340,230,640,580,300,860,280,590,591,250,700,762,592,880,582,930,585,583,305,330,763,1422,584,1262],"request_albums":[{"count":10,"offset":1,"album_type":2,"photo_request":{"return_preview_url":true,"return_large_url":true}}]},"chat_instance_id":"'. $discussion_id .'","message_count":50}}],"is_background":false}';

        $data = $this->post('/mwebapi.phtml?SERVER_OPEN_CHAT',$body);

        $this->handleServerError($data);



        $profile = new Profile();
        $profile->setAppId($discussion_id);
        $profile->setApp(self::APP);

        $messages = array();

        foreach ($data['body'][0]['client_open_chat']['chat_messages'] as $m) {

            $message = new Message();
            $message->setContent($this->parseMessage($m['mssg']));
            $message->setSentDate($m['date_modified']);
            $message->setApp(self::APP);
            $message->setAppId($m['uid']);

            if($m['from_person_id'] == $profile->getAppId()) {
                $message->setProfile($profile);
            } else {
                $message->setProfile($this->getProfile());
            }


            $messages[] = $message;

        }

        $messages = array_reverse($messages);

        return $messages;

    }


    /**
     * @param string $message
     * @return string
     */
    protected function parseMessage(string $message) : string
    {
        $message = str_replace('<br />',"\n",$message);
        $message = htmlspecialchars_decode($message,ENT_QUOTES);

        return $message;
    }

    /**
     * @param $data
     */
    protected function handleServerError($data) {
        if(isset($data['body'][0]['server_error_message'])) {
            throw new BadRequestHttpException($data['body'][0]['server_error_message']['error_message']);
        }
    }


    /**
     * @return bool
     */
    public function isConfigured(): bool
    {

        return $this->cookieJar->getCookieByName('session') != null;

    }

}