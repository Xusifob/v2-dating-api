<?php

namespace App\Services;

use App\Entity\Match;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
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


    const TOKEN = 'bumble_refresh_token';


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

        $this->client = new Client(array(
            'base_uri' => 'https://bumble.com/',
            'headers' => $this->headers,
        ));

        $this->setUser($security->getUser());

    }


    /**
     * @return Profile[]
     */
    function getMatches() : array
    {

        $body = json_decode('{"body":[{"message_type":81,"server_get_encounters":{"number":10,"context":1,"user_field_filter":{"projection":[210,370,200,230,490,540,530,560,291,732,890,930,662,570,380,493,1140,1150,1160,1161],"request_albums":[{"album_type":7},{"album_type":12,"external_provider":12,"count":8}],"game_mode":0,"request_music_services":{"top_artists_limit":8,"supported_services":[29],"preview_image_size":{"width":120,"height":120}}}}}],"message_id":8,"message_type":81,"version":1,"is_background":false}',true);

        $data =  $this->post('/mwebapi.phtml?SERVER_GET_ENCOUNTERS',$body);

        $profiles = array();

        foreach ($data['body'][0]['client_encounters']['results'] as $object)
        {
            $user = $object['user'];
            $profile = new Profile();
            $profile->setAge($user['age']);
            $profile->setAppId($user['user_id']);
            $profile->setFullName($user['name']);
            $profile->setDistance($user['distance_short']);
            if(isset($user['educations'][0]['organization_name'])) {
                $profile->setSchool($user['educations'][0]['organization_name']);
            }
            if(isset($user['jobs'][0]['name'])) {
                $profile->setJobTitle($user['jobs'][0]['name']);
            }

            $bio = '';
            foreach ($user['profile_fields'] as $field) {
                $bio .= "{$field['name']} : {$field['display_value']} \n\r";
            }
            $profile->setBio($bio);

            foreach ($user['albums'][0]['photos'] as $photo) {
                $profile->addPicture('https:' . $photo['large_url']);
            }

            $profile->setApp(self::APP);
            $profiles[] = $profile;
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

      //  $data = json_decode('{"$gpb":"badoo.bma.BadooMessage","message_type":132,"version":1,"message_id":516,"object_type":81,"body":[{"$gpb":"badoo.bma.MessageBody","client_vote_response":{"$gpb":"badoo.bma.ClientVoteResponse","vote_response_type":3,"message":"C\'est réciproque : Julie a aussi voté pour toi !","person_id":"obff5b362880e6595b50bcdadd9b9b4e7a36dd4769cac81ab","current_user_image_id":"//pd1eu.bumbcdn.com/p34/10712/5/9/0/505280068/d1359644/t1549968600/c_YOdCcnKn.S6Pgyq-c0cynGn1VtCReB.0YyjCkJcejCdb3.4Mzl-3AQ/1359644862/dfs_1680y1050/osz___size__.jpg?jpegq=80&wp=1&ck=505280068-1359644862-1680y1050-1549968600&wm_id=15&wm_size=72x72&wm_offs=1971x1297&t=42.1.0.0","other_user_image_id":"//pd1eu.bumbcdn.com/p93/10700/6/2/0/593150016/d1353117/t1534805040/c_u3zePtbvuTRlaRv7XyQR.kwpHOlDze7F-cnkmiTGYhNHSnMuWYhb3g/1353117867/dfs_1680y1050/osz___size__.jpg?jpegq=80&wp=1&ck=593150016-1353117867-1680y1050-1534805040&wm_id=15&wm_size=72x72&wm_offs=1971x1459&t=42.1.0.0","can_chat":false,"other_user_gender":2,"match_mode":0,"accent_color":0},"message_type":132}],"responses_count":1,"is_background":false,"vhost":""}',true);

       // $data = json_decode('{"$gpb":"badoo.bma.BadooMessage","message_type":132,"version":1,"message_id":518,"object_type":81,"body":[{"$gpb":"badoo.bma.MessageBody","client_vote_response":{"$gpb":"badoo.bma.ClientVoteResponse","vote_response_type":1,"message":"","person_id":"obff5b362880e65956a61459e6cf6efef18dac8b6c97e28d3","match_mode":0,"accent_color":0},"message_type":132}],"responses_count":1,"is_background":false,"vhost":""}';

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
        $prefix = $credentials['phone'];

        $number = str_replace(array('+','-',' '),'',$number);
        $prefix = str_replace(array('+','-',' '),'',$prefix);


        $this->cookieJar = new CookieJar();

         throw new BadRequestHttpException("Bumble can't be configured yet");


        // Init the session
        /** @var Response $d */
        $d = $this->rawGetFull('get-started');

        dump($this->cookieJar->toArray());

      //  throw new BadRequestHttpException("Bumble can't be configured yet");

        $body = '{"version":1,"message_type":2,"message_id":1,"body":[{"message_type":2,"server_app_startup":{"app_build":"MoxieWebapp","app_name":"moxie","app_version":"1.0.0","can_send_sms":false,"user_agent":"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36","screen_width":1680,"screen_height":1050,"language":0,"is_cold_start":true,"external_provider_redirect_url":"https://bumble.com/static/external-auth-result.html?","locale":"en","system_locale":"fr-FR","app_platform_type":5,"app_product_type":400,"device_info":{"webcam_available":true,"form_factor":3},"build_configuration":2,"supported_features":[141,145,11,15,1,2,13,46,4,248,6,18,155,70,160,58,140,130,189,187,220,223,100,180,197,161,232,29,227,237,239,254,190],"supported_minor_features":[472,317,2,216,244,232,19,130,225,246,31,125,183,114,254,8,9,83,41,427,115,288,420,477,93,226,413,267,39,290,398,453,180,281,40,455,280,499,471,397,411,352,447,146,469,118,63,391,523,293,431,574,405,547,451,571,319,297,558],"supported_notifications":[83,73,3,72,46,109,81],"supported_payment_providers":[26,100,35,100001],"supported_promo_blocks":[{"context":92,"position":13,"types":[71]},{"context":89,"position":5,"types":[160,358]},{"context":8,"position":13,"types":[111,112,113]},{"context":53,"position":18,"types":[136,93,12]},{"context":45,"position":18,"types":[327]},{"context":45,"position":15,"types":[93,134,135,136,137,327,308,309,334]},{"context":10,"position":1,"types":[265,266,286]},{"context":148,"position":21,"types":[179,180,283]},{"context":130,"position":13,"types":[268,267]},{"context":113,"position":1,"types":[228]},{"context":3,"position":1,"types":[80]},{"context":3,"position":4,"types":[80,228]},{"context":119,"position":1,"types":[80,282,81,90]},{"context":43,"position":1,"types":[96,307]},{"context":10,"position":18,"types":[358,174]},{"context":10,"position":8,"types":[358]},{"context":26,"position":16,"types":[286,371]},{"context":10,"position":6,"types":[286,373,372]}],"supported_user_substitutes":[{"context":1,"types":[3]}],"supported_onboarding_types":[9],"user_field_filter_client_login_success":{"projection":[210,220,230,200,91,890,340,10,11,231,71,93,100]},"a_b_testing_settings":{"tests":[{"test_id":"bumble_web_boom_screen_opens_profile_xp"},{"test_id":"bumble__gifs_with_old_input"}]},"dev_features":["bumble_bizz","bumble_snooze","bumble_questions","bumble__pledge","bumble__request_photo_verification","bumble_moves_making_impact_","bumble__photo_verification_filters","bumble_gift_cards","bumble__antighosting_xp_dead_chat_followup","bumble_private_detector"],"device_id":"2484c695-c695-95a4-a44b-4b478d9aa811","supported_screens":[{"type":23,"version":2},{"type":26,"version":0},{"type":13,"version":0},{"type":14,"version":0},{"type":15,"version":0},{"type":16,"version":0},{"type":17,"version":0},{"type":18,"version":0},{"type":19,"version":0},{"type":20,"version":0},{"type":21,"version":0},{"type":25,"version":0},{"type":27,"version":0},{"type":28,"version":0},{"type":57,"version":0},{"type":29,"version":1},{"type":69,"version":0},{"type":63,"version":0},{"type":92,"version":0},{"type":64,"version":0},{"type":65,"version":0},{"type":66,"version":0},{"type":67,"version":0}],"supported_landings":[{"source":25,"params":[20,3],"search_settings_types":[3]}]}}],"is_background":false}';
        $data = $this->post('mwebapi.phtml?SERVER_APP_STARTUP',$body);

        dump($data);
        die();

        $body = '{"version":1,"message_type":640,"message_id":11,"body":[{"message_type":640,"server_validate_phone_number":{"phone_prefix":"+' . $prefix .'","phone":"' . $number .'","context":203}}],"is_background":false}';
        $data = $this->post('mwebapi.phtml?SERVER_VALIDATE_PHONE_NUMBER',$body);

        dump($data);
        die();

        $body = '{"version":1,"message_type":678,"message_id":10,"body":[{"message_type":678,"server_submit_phone_number":{"phone_prefix":"+'. $prefix .'","phone":"'. $number .'","context":203,"screen_context":{"screen":23}}}],"is_background":false}';
        $data = $this->post('mwebapi.phtml?SERVER_SUBMIT_PHONE_NUMBER',$body);


        dump($data);
        die();

        return true;

    }


    /**
     * @param array $credentials
     * @return bool
     */
    function validateLogin(array $credentials = array()): bool
    {

        $this->parseRequiredArguments($credentials,array('code'));

        $code = $credentials['code'];

        $body = '{"version":1,"message_type":680,"message_id":14,"body":[{"message_type":680,"server_check_phone_pin":{"pin":"'. $code .'","screen_context":{"screen":25}}}],"is_background":false}';


        $data = $this->post('mwebapi.phtml?SERVER_CHECK_PHONE_PIN',$body);

        if(isset($data['body'][0]['server_error_message'])) {
            throw new BadRequestHttpException($data['body'][0]['server_error_message']['error_message']);
        }

        return true;

    }


    /**
     * @param User $user
     */
    public function setUser(User $user)
    {

        $this->user = $user;
        $this->cookieJar = CookieJar::fromArray(array(
            'session' => $this->user->get(self::TOKEN),
        ),'bumble.com');
    }


    /**
     * @return User
     */
    public function disconnect() : User
    {
        $this->user->setBumbleRefreshToken(null);

        $this->em->persist($this->user);
        $this->em->flush();

        $this->setUser($this->user);

        return $this->user;
    }


    /**
     * @return array
     */
    public function getMessageList() : array
    {

        return array();

        $body = '{"version":1,"message_type":245,"message_id":5,"body":[{"message_type":245,"server_get_user_list":{"user_field_filter":{"projection":[200,340,230,640,580,300,860,280,590,591,250,700,762,592,880,582,930,585,583,305,330,763,1422,584,1262]},"preferred_count":30,"folder_id":0}}],"is_background":false}';

        $data = $this->post('mwebapi.phtml?SERVER_GET_USER_LIST',$body);

        echo json_encode($data);

        die();


    }


    /**
     * @return bool
     */
    public function isConfigured(): bool
    {

        return $this->user->getBumbleRefreshToken() !== null;

    }

}