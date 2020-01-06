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
class BadooService extends APIService
{

    /**
     * @var Client
     */
    protected $client;


    const APP = 'badoo';



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
        $this->headers['Origin'] = 'https://eu1.badoo.com';
        $this->headers['Host'] = 'badoo.com';
        $this->headers['Content-Type'] = 'json';
        $this->headers['X-Desktop-web'] = 1;

        $stack = HandlerStack::create();
        $stack->push(EffectiveMiddlewareService::middleware());

        $this->client = new Client(array(
            'base_uri' => 'https://eu1.badoo.com/',
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

        $body = '{"version":1,"message_type":81,"message_id":3,"body":[{"message_type":81,"server_get_encounters":{"number":20,"context":1,"user_field_filter":{"projection":[200,230,210,301,680,303,304,290,291,490,800,330,331,460,732,370,410,740,742,311,10005,662,560,770,870],"request_albums":[{"album_type":7}],"united_friends_filter":[{"count":5,"section_type":3},{"count":5,"section_type":1},{"count":5,"section_type":2}]}}}],"is_background":false}';


        $data =  $this->post('/webapi.phtml?SERVER_GET_ENCOUNTERS',$body);


        foreach ($data['body'][0]['client_encounters']['results'] as $object)
        {

            $user = $object['user'];



            $profile = new Profile();
            $profile->setAge($user['age']);
            $profile->setAppId($user['user_id']);
            $profile->setFullName($user['name']);

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
     * @param Profile $profile
     * @return array
     */
    function like(Profile $profile) : Match
    {
        $body = '{"version":1,"message_type":80,"message_id":14,"body":[{"message_type":80,"server_encounters_vote":{"person_id":"'. $profile->getAppId() .'","vote":3,"photo_id":"304102-42","vote_source":1}}],"is_background":false}';

        $data = $this->post('/webapi.phtml?SERVER_ENCOUNTERS_VOTE',$body);

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

        $body = '{"version":1,"message_type":80,"message_id":20,"body":[{"message_type":80,"server_encounters_vote":{"person_id":"'. $profile->getAppId() .'","vote":2,"vote_source":1}}],"is_background":false}';

        $data =  $this->post('/webapi.phtml?SERVER_ENCOUNTERS_VOTE',$body);

        $this->handleServerError($data);

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

        $this->parseRequiredArguments($credentials,array('session'));

        $cookies = array(
            array(
                "Name" => 's1',
                "Domain" => '.badoo.com',
                'Value' => urldecode($credentials['session']),
            ),array(
                "Name" => 'session_cookie_name',
                "Domain" => '.badoo.com',
                'Value' => 's1',
            )
        );

        $this->setCookieJar($cookies);

        try{

            $body = '{"version":1,"message_type":2,"message_id":1,"body":[{"message_type":2,"server_app_startup":{"app_build":"Badoo","app_name":"hotornot","app_version":"1.0.00","can_send_sms":false,"user_agent":"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36","screen_width":1920,"screen_height":1080,"language":0,"locale":"fr-FR","app_platform_type":5,"app_product_type":100,"device_info":{"screen_density":1,"form_factor":3,"webcam_available":true},"supported_streaming_sdk":[3,5,6],"build_configuration":2,"supported_features":[1,58,2,4,6,7,8,172,9,10,11,12,13,15,18,19,20,21,25,27,28,29,32,34,35,46,36,37,39,42,44,54,62,64,70,73,74,75,78,83,96,99,100,103,105,106,107,108,109,111,113,114,125,129,10003,91,116,81,136,104,132,142,123,127,169,157,161,181,183,135,179,209,197,248,259,242,243,210,211,214,148],"supported_minor_features":[292,444,93,267,111,40,41,12,22,59,61,52,25,21,74,31,129,24,19,115,48,69,86,90,81,245,132,118,36,125,65,143,80,131,114,251,89,104,8,135,137,2,148,83,164,163,20,171,142,157,102,139,103,179,207,180,188,189,219,202,159,187,178,136,226,146,210,169,208,218,196,122,127,175,194,134,244,253,183,214,184,181,182,259,242,261,306,130,269,268,266,313,312,153,168,230,63,305,285,274,348,328,364,254,291,394,382,365,403,465,280,396,420,439,470,474,397,493,483,576,440,450,548,549,390,530],"supported_notifications":[100,3,25,40,41,47,9,50,55,39,38,42,62,60,66,76,73,81,96,98,108,35,33],"supported_promo_blocks":[{"context":1,"position":2,"types":[8,37,56,57]},{"context":2,"position":1,"types":[8,37,56,57]},{"context":2,"position":2,"types":[8,37,56,57]},{"context":22,"position":1,"types":[8,37,56,57]},{"context":22,"position":2,"types":[8,37,56,57]},{"context":6,"position":1,"types":[8,37,56,57]},{"context":6,"position":2,"types":[8,37,56,57]},{"context":23,"position":1,"types":[8,37,56,57]},{"context":23,"position":2,"types":[8,37,56,57]},{"context":26,"position":4,"types":[165]},{"context":26,"position":1,"types":[165,56,7,57]},{"context":26,"position":10,"types":[143]},{"context":10,"position":1,"types":[70]},{"context":32,"position":1,"types":[8,1,9,10,56,57]},{"context":45,"position":15,"types":[137,222,1,9,10,56,230,258,285,333]},{"context":45,"position":13,"types":[12]},{"context":92,"position":13,"types":[120,68,71,210,12]},{"context":138,"position":13,"types":[150,159]},{"context":138,"position":10,"types":[83]},{"context":106,"position":13,"types":[122]},{"context":43,"position":4,"types":[164]},{"context":43,"position":1,"types":[1,9,10,24,22,48,59,56]},{"context":27,"position":4,"types":[164]},{"context":27,"position":1,"types":[7,3,6,43,37,4,5]},{"context":35,"position":13,"types":[8]},{"context":35,"position":4,"types":[7,6]},{"context":35,"position":1,"types":[4,5,35,40,37]},{"context":151,"position":8,"types":[7,6,5,4,43,40,37,9,1,10,11,56,57]},{"context":105,"position":13,"types":[42,83]},{"context":174,"position":13,"types":[236]},{"context":176,"position":16,"types":[252]},{"context":153,"position":21,"types":[193]},{"context":153,"position":1,"types":[193]},{"context":153,"position":13,"types":[193]},{"context":153,"position":22,"types":[194]},{"context":3,"position":4,"types":[8]},{"context":3,"position":20,"types":[326]}],"supported_onboarding_types":[2,1,23,30,32,33],"supported_server_sharing_contexts":[124],"external_provider_apps":[12],"supported_payment_providers":[110004,100,35,100001,26,102,143,501,502,170,160],"supported_user_substitutes":[{"context":1,"types":[5]},{"context":26,"types":[5]}],"user_field_filter_webrtc_start_call":{"projection":[200,490]},"user_field_filter_client_login_success":{"projection":[310,90,490,290,100,291,50,230,340,30,200,210,690,91,93,460,10,440,220,1000]},"user_field_filter_chat_message_from":{"projection":[210,230,200]},"hotpanel_session_id":"6e4cf342-743d-4094-b5fb-ad634f06b46c","device_id":"89875764-5764-644d-4d4c-4c457c8618fa","a_b_testing_settings":{"tests":[{"test_id":"badoo__web__liked_you_screen_"},{"test_id":"badoo_web_profile_onboarding"},{"test_id":"cach_first_encounter_card"},{"test_id":"web_chat_filter"},{"test_id":"badoo_web_empty_encounter_profile"},{"test_id":"encounter_40"},{"test_id":"encounter_smart_about_me"},{"test_id":"navigation_in_pnb_cards"},{"test_id":"dw_gender_icons"},{"test_id":"log_out_instead_of_delete"},{"test_id":"web_fullscreen_paywall"},{"test_id":"new_entry_point_for_simplified_profile_quality_walk_through"},{"test_id":"xpdw__profile_quality_walkthrough_payer"},{"test_id":"web__crush__1_click_flow"}]},"dev_features":["refactoring_gallery","web_lookalikes_infinity_scroll","simplified_verification"]}}],"is_background":false}';

            $data = $this->post('/webapi.phtml?SERVER_APP_STARTUP',$body);

            foreach ($data['body'] as $datum) {
                if(!isset($datum['client_login_success'])) {
                    continue;
                }

                $userId = $datum['client_login_success']['user_info']['user_id'];

                $this->user->setBadooUserId($userId);
                $this->em->persist($this->user);
                $this->em->flush();

                $this->setUser($this->user);


            }

            //$this->getMatches();
        }catch (\Exception $exception) {

            throw new BadRequestHttpException("Identifiant de session incorrect");
        }

        return true;

        $this->parseRequiredArguments($credentials,array('mail','password'));

        $mail = $credentials['mail'];
        $password = $credentials['password'];

        $this->cookieJar = new CookieJar();

        $d = $this->get('https://badoo.com/signin/');

        // Init app

        $body = '{"version":1,"message_type":2,"message_id":1,"body":[{"message_type":2,"server_app_startup":{"app_build":"Badoo","app_name":"hotornot","app_version":"1.0.00","can_send_sms":false,"user_agent":"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36","screen_width":1920,"screen_height":1080,"language":0,"locale":"fr-FR","app_platform_type":5,"app_product_type":100,"device_info":{"screen_density":1,"form_factor":3,"webcam_available":true},"supported_streaming_sdk":[3,5,6],"build_configuration":2,"supported_features":[1,58,2,4,6,7,8,172,9,10,11,12,13,15,18,19,20,21,25,27,28,29,32,34,35,46,36,37,39,42,44,54,62,64,70,73,74,75,78,83,96,99,100,103,105,106,107,108,109,111,113,114,125,129,10003,91,116,81,136,104,132,142,123,127,169,157,161,181,183,135,179,209,197,248,259,242,243,210,211,214,148],"supported_minor_features":[292,444,93,267,111,40,41,12,22,59,61,52,25,21,74,31,129,24,19,115,48,69,86,90,81,245,132,118,36,125,65,143,80,131,114,251,89,104,8,135,137,2,148,83,164,163,20,171,142,157,102,139,103,179,207,180,188,189,219,202,159,187,178,136,226,146,210,169,208,218,196,122,127,175,194,134,244,253,183,214,184,181,182,259,242,261,306,130,269,268,266,313,312,153,168,230,63,305,285,274,348,328,364,254,291,394,382,365,403,465,280,396,420,439,470,474,397,493,483,576,440,450,548,549,390,530],"supported_notifications":[100,3,25,40,41,47,9,50,55,39,38,42,62,60,66,76,73,81,96,98,108,35,33],"supported_promo_blocks":[{"context":1,"position":2,"types":[8,37,56,57]},{"context":2,"position":1,"types":[8,37,56,57]},{"context":2,"position":2,"types":[8,37,56,57]},{"context":22,"position":1,"types":[8,37,56,57]},{"context":22,"position":2,"types":[8,37,56,57]},{"context":6,"position":1,"types":[8,37,56,57]},{"context":6,"position":2,"types":[8,37,56,57]},{"context":23,"position":1,"types":[8,37,56,57]},{"context":23,"position":2,"types":[8,37,56,57]},{"context":26,"position":4,"types":[165]},{"context":26,"position":1,"types":[165,56,7,57]},{"context":26,"position":10,"types":[143]},{"context":10,"position":1,"types":[70]},{"context":32,"position":1,"types":[8,1,9,10,56,57]},{"context":45,"position":15,"types":[137,222,1,9,10,56,230,258,285,333]},{"context":45,"position":13,"types":[12]},{"context":92,"position":13,"types":[120,68,71,210,12]},{"context":138,"position":13,"types":[150,159]},{"context":138,"position":10,"types":[83]},{"context":106,"position":13,"types":[122]},{"context":43,"position":4,"types":[164]},{"context":43,"position":1,"types":[1,9,10,24,22,48,59,56]},{"context":27,"position":4,"types":[164]},{"context":27,"position":1,"types":[7,3,6,43,37,4,5]},{"context":35,"position":13,"types":[8]},{"context":35,"position":4,"types":[7,6]},{"context":35,"position":1,"types":[4,5,35,40,37]},{"context":151,"position":8,"types":[7,6,5,4,43,40,37,9,1,10,11,56,57]},{"context":105,"position":13,"types":[42,83]},{"context":174,"position":13,"types":[236]},{"context":176,"position":16,"types":[252]},{"context":153,"position":21,"types":[193]},{"context":153,"position":1,"types":[193]},{"context":153,"position":13,"types":[193]},{"context":153,"position":22,"types":[194]},{"context":3,"position":4,"types":[8]},{"context":3,"position":20,"types":[326]}],"supported_onboarding_types":[2,1,23,30,32,33],"supported_server_sharing_contexts":[124],"external_provider_apps":[12],"supported_payment_providers":[110004,100,35,100001,26,102,143,501,502,170,160],"supported_user_substitutes":[{"context":1,"types":[5]},{"context":26,"types":[5]}],"user_field_filter_webrtc_start_call":{"projection":[200,490]},"user_field_filter_client_login_success":{"projection":[310,90,490,290,100,291,50,230,340,30,200,210,690,91,93,460,10,440,220,1000]},"user_field_filter_chat_message_from":{"projection":[210,230,200]},"hotpanel_session_id":"a1cf7166-aeea-464b-b563-67ed6dfeaab5","device_id":"89875764-5764-644d-4d4c-4c457c8618fa","a_b_testing_settings":{"tests":[{"test_id":"badoo__web__liked_you_screen_"},{"test_id":"badoo_web_profile_onboarding"},{"test_id":"cach_first_encounter_card"},{"test_id":"web_chat_filter"},{"test_id":"badoo_web_empty_encounter_profile"},{"test_id":"encounter_40"},{"test_id":"encounter_smart_about_me"},{"test_id":"navigation_in_pnb_cards"},{"test_id":"dw_gender_icons"},{"test_id":"log_out_instead_of_delete"},{"test_id":"web_fullscreen_paywall"},{"test_id":"new_entry_point_for_simplified_profile_quality_walk_through"},{"test_id":"xpdw__profile_quality_walkthrough_payer"},{"test_id":"web__crush__1_click_flow"}]},"dev_features":["refactoring_gallery","web_lookalikes_infinity_scroll","simplified_verification"]}}],"is_background":false}';
        $data = $this->post('/webapi.phtml?SERVER_APP_STARTUP',$body);

        $body = '{"version":1,"message_type":15,"message_id":2,"body":[{"message_type":15,"server_login_by_password":{"remember_me":true,"user":"'. $mail .'","password":"'. $password .'"}}],"is_background":false}';
        $data = $this->post('/webapi.phtml?SERVER_LOGIN_BY_PASSWORD',$body);

        dump($data);
        die();

        $this->handleServerError($data);

        dump($data);

        die();

        if(isset($data['body'][0]['client_validate_user_field']['valid']) && !$data['body'][0]['client_validate_user_field']['valid']) {
            throw new BadRequestHttpException($data['body'][0]['client_validate_user_field']['error_message']);
        }


        $body = '{"version":1,"message_type":678,"message_id":10,"body":[{"message_type":678,"server_submit_phone_number":{"phone_prefix":"+'. $prefix .'","phone":"'. $number .'","context":203,"screen_context":{"screen":23}}}],"is_background":false}';
        $data = $this->post('/mwebapi.phtml?SERVER_SUBMIT_PHONE_NUMBER',$body);

        return true;

    }


    public function validateLogin(array $credentials = array()): bool
    {
        throw new BadRequestHttpException("Validate login doesn't exist in app  " . $this::APP);
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

        $this->user->setBadooUserId(null);
        $this->em->persist($this->user);
        $this->em->flush();

        $this->setUser($this->user);


        return $this->user;
    }


    public function updateLocation($lat, $long) : bool
    {
        $body = '{"version":1,"message_type":4,"message_id":8,"body":[{"message_type":4,"server_update_location":{"location":[{"longitude":2.213749,"latitude":46.227638}]}}],"is_background":false}';

        $this->post('/mwebapi.phtml?SERVER_UPDATE_LOCATION',$body);

        return true;
    }


    /**
     * @return array
     */
    public function getMessageList() : array
    {


        $body = '{"version":1,"message_type":468,"message_id":29,"body":[{"message_type":468,"server_open_messenger":{"contacts_user_field_filter":{"projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"profile_photo_size":{"square_face_photo_size":{"width":100,"height":100}}},"chat_user_field_filter":{"projection":[330,331,200,700,580,640,600,610,250,340,280,290,291,310,301,680,303,304,210,230,731,650,570,280,490,410,370,670,560,550,762,930],"profile_photo_size":{"square_face_photo_size":{"width":300,"height":300}},"request_albums":[{"count":20,"offset":1,"album_type":2,"photo_request":{"return_preview_url":true,"return_large_url":true}}]},"initial_screen_user_field_filter":{"projection":[],"united_friends_filter":[{"section_type":1}]}}}],"is_background":false}';

        $data = $this->post('/webapi.phtml?SERVER_OPEN_MESSENGER',$body);

        //$data = json_decode('{"$gpb":"badoo.bma.BadooMessage","message_type":469,"version":1,"message_id":29,"object_type":310,"body":[{"$gpb":"badoo.bma.MessageBody","client_open_messenger":{"$gpb":"badoo.bma.ClientOpenMessenger","contacts":{"$gpb":"badoo.bma.ClientUserList","section":[{"$gpb":"badoo.bma.ListSection","section_id":"1","total_count":26,"last_block":false,"add_feature":{"$gpb":"badoo.bma.ApplicationFeature","feature":44,"enabled":false,"required_action":10},"allowed_actions":[1],"users":[{"$gpb":"badoo.bma.User","user_id":"755477482","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Tsilavina Aron","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"361321","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p95\/10640\/8\/3\/6\/755477482\/d361\/t1577882616\/c_1RL4B7EqtUh6cFoAvPAsMYiJakC.OZHzX7.JauWpppJ2lc4fWmAPQw\/361321\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Tsilavina Aron veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"754424137","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Luffy","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"342846","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p16\/10717\/3\/5\/6\/754424137\/d342\/t1577479316\/c_lbG.CmYDZZJ8E.CU0EVqUkKy24y-DdxcYEe-ls398hO2r9NrDb4SJw\/342846\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Luffy veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"755088986","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Jessica","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"4931287","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p77\/10705\/1\/1\/1\/755088986\/d4931\/t1577722896\/c_ZJuI0fu8gfikXSH3y4GwBQ2MxJz0cIRJjyEVRNIJ.1Ijh0OtuPgQtQ\/4931287\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Jessica veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"12875192","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Marie","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"603641","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p55\/10164\/1\/6\/3\/12875192\/d603\/t1535162792\/c_znm8VZGjzEfzg97I0EZgy1pS5JXLIUUCm.j7HV9UqYrCUlz40zjQ9g\/603641\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Marie veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"692911645","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Nom mod\u00e9r\u00e9","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1345744052","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p84\/30193\/4\/8\/9\/692911645\/d1345744\/t1553852976\/c_M6RHl4gOA-BT.jqlBBJJtMcxm2ykIueKgPYf7LGByXHWKC1A7ATBgA\/1345744052\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Nom mod\u00e9r\u00e9 veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"713098417","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Kali","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1347694267","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p33\/30150\/4\/2\/9\/713098417\/d1347694\/t1575565299\/c_Qe46daS284.nCCuyOvLk4bZe2j49CPLoLaAg.DQesYAx9pkaQ72aEA\/1347694267\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Kali veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"629202844","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Maggy","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1347485047","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p78\/30153\/0\/3\/9\/629202844\/d1347485\/t1577981049\/c_XcRZfesI.J1dYRaKHZ.SSy9L8BIt6yq-cOkEKpWQjWvm407w6IFqcA\/1347485047\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Maggy veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"388524602","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Anne","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"2581154","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p93\/10251\/0\/6\/0\/388524602\/d2581\/t1401293263\/c_gkyFYbJipGrxJlDY8QzJ2J9QfjeBKI4msBux8fDUzXCIqJ8fMsc.bg\/2581154\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Anne veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"592890974","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Georgia elouga","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1335350815","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p4\/10586\/8\/4\/0\/592890974\/d1335350\/t1507672171\/c_sb17BMJKfXUk5Xd.os3KuHiuRA.IessGQQBRU3lR99TjtQRUwYntEA\/1335350815\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Georgia elouga veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"553111204","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Bella","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1328474376","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p17\/126\/5\/5\/4\/553111204\/d1328474\/t1486229508\/c_-qgN97STxB5KQ.VuaIWqVwAqfHucS0yv6.jXAuMdlG2WdBcdYz8.Wg\/1328474376\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Hello Bella \ud83d\udc4b"},{"$gpb":"badoo.bma.User","user_id":"548938214","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Monica","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1328324736","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p95\/30164\/3\/0\/7\/548938214\/d1328324\/t1483629939\/c_t6--GbdLC5QQQiGJknuEPIp8gA6oBu5CPxl6E4MMLkpMLkOfWgsW2A\/1328324736\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Hello Monica \ud83d\ude18"},{"$gpb":"badoo.bma.User","user_id":"277455643","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Gwen","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"461104","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p89\/10466\/3\/3\/3\/277455643\/d461\/t1416812676\/c_OdNLPeNXpeZ2XSdepshpiQg.YNK-X1YJ7LkMIxnCxeETwUP08oHkTQ\/461104\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Gwen veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"550574907","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Manuella","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1328419488","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p72\/30018\/3\/9\/4\/550574907\/d1328419\/t1484680279\/c_AsWT2YsovTw4GKO99gqwS9XrsghFh8NzpdJgCsVj3WWtZFovBDEAjw\/1328419488\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Manuella veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"546033852","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Nina","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1354974257","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p77\/30228\/5\/8\/8\/546033852\/d1354974\/t1558718926\/c_TdPatNDG5c.I4raMJbUIRfmmBB-5gLH8J4OCBh.V3YghRcibpie6Dw\/1354974257\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Nina veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"532830449","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Kelly","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1327450415","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p61\/10489\/9\/5\/4\/532830449\/d1327450\/t1478640673\/c_rlpB9tLOtWe8P7XDy7p8ClLU7e4nWu2o5pUlG5dEe9d0HN6xHSCzWw\/1327450415\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":true,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Match il y a quelque temps"},{"$gpb":"badoo.bma.User","user_id":"162960072","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Fanny","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"564485","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p78\/10275\/6\/7\/5\/162960072\/d564\/t1273408255\/c_R7O7Xx4WXJO35syq1DOclGF1meAJWCd.XOUcgaYYKpnUrE3mfE0K5g\/564485\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Fanny veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"548319504","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Judith","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1328331086","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p93\/30119\/9\/7\/5\/548319504\/d1328331\/t1483202433\/c_WS1dUNjedTOLvhTHHljYH68mSY8ZDWqkt7rKL5yDWrmGp3-npxY.WQ\/1328331086\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Judith veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"1400728477","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Marie-Line","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1327662169","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2us.badoocdn.com\/p583\/20167\/6\/4\/1\/1400728477\/d1327662\/t1482177744\/c_SnvDBk1y8SPkUoBqd14cCCt-GFuHCJenIBc2y3b4GrQ.t-bkEG-1iA\/1327662169\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Marie-Line veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"1300033198","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Monique","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"264647413","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2us.badoocdn.com\/p564\/20320\/3\/1\/9\/1300033198\/d264647\/t1408658483\/c_jjzLkUzqTmg-wRti2Ya7JSXd4T2eAKgMDN7Az5us-wSO6Naqc9X2zA\/264647413\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Monique veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"1378752397","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Lisianne","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1322280219","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2us.badoocdn.com\/p576\/20696\/4\/2\/0\/1378752397\/d1322280\/t1465516306\/c_RK2cHbyzVK8WmiQbNaYL2OaR3xecIHPMi6jd41tJsC2gopGClQvVNg\/1322280219\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Lisianne veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"1236843833","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Sarah-maude","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"309255598","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2us.badoocdn.com\/p546\/20434\/5\/8\/4\/1236843833\/d309255\/t1475512448\/c_RjnplQIrKTaGp2PoCu8ZWlwV28ptp66AuBhykaYkwd8life55jrDRA\/309255598\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Sarah-maude veut tchatter"},{"$gpb":"badoo.bma.User","user_id":"1391574205","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Dorothy","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"543","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2us.badoocdn.com\/p569\/40109\/6\/4\/4\/1391574205\/d0\/t1474957156\/c_5L3N-mr8RK3exej5v6.Srn5fBVTXNMFD1z-06tYtYkEH.5Mys40uWA\/543\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":true,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Match il y a quelque temps"},{"$gpb":"badoo.bma.User","user_id":"1391414169","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Loveth","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"2615","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2us.badoocdn.com\/p585\/20521\/6\/7\/5\/1391414169\/d2\/t1474842200\/c_u8pTZAhnT.9KMQtrgST2vFIwEb6meQ9XBY39DeUbH0USDispykLCVQ\/2615\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":true,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Match il y a quelque temps"},{"$gpb":"badoo.bma.User","user_id":"1380173110","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Dianne","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"1326865905","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2us.badoocdn.com\/p587\/20727\/4\/0\/9\/1380173110\/d1326865\/t1474656559\/c_64XWIBdysSA5mrnUbVBSxr7INIl6vEOKGRVKWGqfV.fzmDPmA4X3lQ\/1326865905\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":true,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Match il y a quelque temps"},{"$gpb":"badoo.bma.User","user_id":"1134276271","projection":[330,331,200,700,580,640,600,610,250,340,280,230,650,501],"access_level":10,"name":"Nath","gender":2,"is_deleted":false,"is_unread":false,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"3543634","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2us.badoocdn.com\/p523\/20419\/4\/0\/4\/1134276271\/d3543\/t1498900308\/c_P-YsXJm1jyrFNqyQE0YFNtaibV5H..VX5m.myqauJQRRPDqKdwsYww\/3543634\/dfs_100x100\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"is_match":true,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"display_message":"Match il y a quelque temps"}],"sync_token":"AXsiY3JlYXRlZCI6MTU3ODA2OTU5Mi43MjkxMTYsImRpcmVjdGlvbiI6MiwiZmlsdGVycyI6W10sImZvbGRlcl90eXBlIjowLCJsYXN0X3VzZXJfaWQiOiI2MjkyMDI4NDQiLCJzZWN0aW9uX2lkIjoxLCJ0b2tlbl90eXBlIjoyLCJ1cGRhdGVfdGltZXN0YW1wIjoxNTc4MDYyMjY3fQ==","page_token":"AXsiY3JlYXRlZCI6MTU3ODA2OTU5Mi43MjkwNTcsImRpcmVjdGlvbiI6MSwiZmlsdGVycyI6W10sImZvbGRlcl90eXBlIjowLCJsYXN0X3VzZXJfaWQiOiIxMTM0Mjc2MjcxIiwic2VjdGlvbl9pZCI6MSwic29ydF90aW1lc3RhbXAiOjE0NzQ2NTIwNTcsInRva2VuX3R5cGUiOjF9"}],"total_sections":1,"total_count":26,"promo_banners":[{"$gpb":"badoo.bma.PromoBlock","mssg":"","action":"","header":"Tes messages en priorit\u00e9\u00a0!","ok_action":6,"other_text":"","pictures":[{"$gpb":"badoo.bma.ApplicationFeaturePicture","display_images":"\/\/pd2us.badoocdn.com\/p577\/hidden?euri=1-c-n.n0Gq3zEHgYZrRs-RPFwG.Sfm3O7wtmLqGx4SxWm.CJXmSeDmyfVNREbkeBUXXNSRvH8UGfLoV3gc74znzt4QXlvS36QGErydowiP3oYK2HaLWVSlmeqlYJUkShWEhH50Eh.bwftkTJZWlnc4qEX7yIWT6lYSWAxMJPiTav0rVbl.jrvg&size=__size__","badge_type":19}],"ok_payment_product_type":1,"promo_block_type":16,"promo_block_position":1,"credits_cost":"","unique_id":"de9772cc3d7b3067eedbe44f48e77f1a","stats_required":[1],"actions_changing_visibility":[6,46,60]}],"page_token":"AXsiY3JlYXRlZCI6MTU3ODA2OTU5Mi43MjkwNTcsImRpcmVjdGlvbiI6MSwiZmlsdGVycyI6W10sImZvbGRlcl90eXBlIjowLCJsYXN0X3VzZXJfaWQiOiIxMTM0Mjc2MjcxIiwic2VjdGlvbl9pZCI6MSwic29ydF90aW1lc3RhbXAiOjE0NzQ2NTIwNTcsInRva2VuX3R5cGUiOjF9","sync_token":"AXsiY3JlYXRlZCI6MTU3ODA2OTU5Mi43MjkxMTYsImRpcmVjdGlvbiI6MiwiZmlsdGVycyI6W10sImZvbGRlcl90eXBlIjowLCJsYXN0X3VzZXJfaWQiOiI2MjkyMDI4NDQiLCJzZWN0aW9uX2lkIjoxLCJ0b2tlbl90eXBlIjoyLCJ1cGRhdGVfdGltZXN0YW1wIjoxNTc4MDYyMjY3fQ==","delay_sec":10},"active_chat":{"$gpb":"badoo.bma.ClientOpenChat","chat_instance":{"$gpb":"badoo.bma.ChatInstance","uid":"755477482","date_modified":1578062205,"counter":1,"their_icon_id":"","my_icon_id":"\/\/pd2eu.badoocdn.com\/p5\/10351\/3\/5\/6\/459577495\/d1328525\/t1485958982\/c_Nq7wwEHnIfpo3ikHCGFQV-t2ofD8Zbh-XQJOBLHSpSjhk8FiDkUdTQ\/1328525353\/dfs_120x120\/sz___size__.jpg?jpegq=80&t=30.1.0.00","other_account_deleted":false,"is_new":false,"feels_like_chatting":false,"my_unread_messages":0,"their_unread_messages":1,"is_match":false,"open_stickers":false},"max_unanswered_messages":2,"is_chat_available":true,"user_originated_message":false,"chat_settings":{"$gpb":"badoo.bma.ChatSettings","chat_instance_id":"755477482","multimedia_settings":{"$gpb":"badoo.bma.MultimediaSettings","feature":{"$gpb":"badoo.bma.ApplicationFeature","feature":70,"enabled":false,"required_action":1,"display_message":"Tu dois attendre une r\u00e9ponse avant de pouvoir envoyer une photo.","display_title":"Attends sa r\u00e9ponse","display_action":"OK"},"multimedia_config":{"$gpb":"badoo.bma.MultimediaConfig","visibility":[{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":4,"seconds":-1,"display_value":"Illimit\u00e9"},{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":3,"seconds":10,"display_value":"10 secondes"},{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":2,"seconds":5,"display_value":"5 secondes"},{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":1,"seconds":2,"display_value":"2 secondes"}],"format":1},"multimedia_configs":[{"$gpb":"badoo.bma.MultimediaConfig","visibility":[{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":4,"seconds":-1,"display_value":"Illimit\u00e9"},{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":3,"seconds":10,"display_value":"10 secondes"},{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":2,"seconds":5,"display_value":"5 secondes"},{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":1,"seconds":2,"display_value":"2 secondes"}],"format":1},{"$gpb":"badoo.bma.MultimediaConfig","visibility":[{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":5,"display_value":"1 fois"},{"$gpb":"badoo.bma.MultimediaVisibility","visibility_type":4,"display_value":"Illimit\u00e9"}],"format":2,"min_length":0,"max_length":600}]},"feature_order":[18,70,111,113,73],"input_settings":{"$gpb":"badoo.bma.InputSettings","input_features":[{"$gpb":"badoo.bma.ApplicationFeature","feature":18,"enabled":true},{"$gpb":"badoo.bma.ApplicationFeature","feature":70,"enabled":false},{"$gpb":"badoo.bma.ApplicationFeature","feature":111,"enabled":true},{"$gpb":"badoo.bma.ApplicationFeature","feature":113,"enabled":true},{"$gpb":"badoo.bma.ApplicationFeature","feature":157,"enabled":false},{"$gpb":"badoo.bma.ApplicationFeature","feature":73,"enabled":true}]}},"chat_messages":[{"$gpb":"badoo.bma.ChatMessage","uid":"4307875822","date_modified":1577898824,"from_person_id":"755477482","to_person_id":"459577495","mssg":"Tsilavina Aron veut tchatter","message_type":1,"read":true,"album_id":"0","total_unread":0,"unread_from_user":0,"image_url":"","frame_url":"","can_delete":false,"deleted":false,"section_title":"1\u00a0janvier\u00a02020","from_person_info":{"$gpb":"badoo.bma.ChatUserInfo"},"sticker":{"$gpb":"badoo.bma.Sticker"},"gift":{"$gpb":"badoo.bma.PurchasedGift"},"offensive":false,"display_message":"","verification_method":{"$gpb":"badoo.bma.VerificationAccessObject"},"date_created":1577898824,"access_response_type":0,"reply_to_uid":"","first_response":false,"video_call_msg_info":{"$gpb":"badoo.bma.VideoCallMsgInfo"},"is_masked":false,"emojis_count":0,"has_emoji_characters_only":false,"user_substitute_id":"","allow_reply":false,"allow_edit_until_timestamp":0,"is_edited":false,"allow_forwarding":false,"clear_chat_version":1346372546,"story":{"$gpb":"badoo.bma.Story"},"is_declined":false,"has_lewd_photo":false,"experimental_gift":{"$gpb":"badoo.bma.ExperimentalGift"}}],"chat_user":{"$gpb":"badoo.bma.User","user_id":"755477482","projection":[330,331,200,700,580,640,600,610,250,340,280,290,291,310,301,680,303,304,210,230,731,650,570,280,490,410,370,670,560,550,762,930],"client_source":10,"access_level":10,"name":"Tsilavina Aron","age":32,"gender":2,"is_deleted":false,"is_unread":false,"is_verified":false,"verification_status":1,"has_spp":true,"has_riseup":false,"photo_count":0,"online_status":4,"online_status_text":"Ton statut est cach\u00e9","profile_photo":{"$gpb":"badoo.bma.Photo","id":"361321","large_url":"","large_photo_size":{"$gpb":"badoo.bma.PhotoSize"},"square_face_url":"\/\/pd2eu.badoocdn.com\/p95\/10640\/8\/3\/6\/755477482\/d361\/t1577882616\/c_lC0arqWGF7k-wKwUX6ekdf6gUaYl16Bgb.u-2fXgVZ8JRhe0u6Pzqw\/361321\/dfs_300x300\/sz___size__.jpg?jpegq=80&t=30.1.0.00"},"albums":[{"$gpb":"badoo.bma.Album","uid":"me:755477482","name":"Photos de Tsilavina Aron","owner_id":"755477482","access_type":0,"accessable":true,"adult":false,"requires_moderation":false,"count_of_photos":2,"is_upload_forbidden":false,"photos":[{"$gpb":"badoo.bma.Photo","id":"362368","preview_url":"\/\/pd2eu.badoocdn.com\/p95\/10640\/8\/3\/6\/755477482\/d362\/t1577898641\/c_VEGdbKdfteeDfFUeBObXqn0tdEghaVtDxse7ZBtfYwUXCpT.p9LlPg\/362368\/dfs_192y192\/sz___size__.jpg?jpegq=80&t=30.1.0.00","large_url":"\/\/pd2eu.badoocdn.com\/p95\/10640\/8\/3\/6\/755477482\/d362\/t1577898641\/c_LMFwF0k3fHQTwy4UqwXOjLHytfbySdwOsWhwXFa5RClE-NTr-Md5dw\/362368\/dfs_920y920\/osz___size__.jpg?jpegq=80&wm_id=8&wm_size=88x88&wm_offs=16x16&t=30.1.0.00","large_photo_size":{"$gpb":"badoo.bma.PhotoSize","width":517,"height":920},"face_top_left":{"$gpb":"badoo.bma.Point","x":153,"y":253},"face_bottom_right":{"$gpb":"badoo.bma.Point","x":319,"y":419},"rating":{"$gpb":"badoo.bma.PhotoRating","rate_action_message":"N\'oublie pas que les photos de groupes ne sont pas not\u00e9es","rate_type":25},"is_pending_moderation":false,"preview_url_expiration_ts":1585699200,"large_url_expiration_ts":1585699200}],"album_type":2}],"interests_in_common":0,"profile_fields":[{"$gpb":"badoo.bma.ProfileField","id":"location","type":1,"name":"Emplacement","display_value":"Cachan, Val-de-Marne"},{"$gpb":"badoo.bma.ProfileField","id":"aboutme_text","type":2,"name":"\u00c0 propos de moi","display_value":""},{"$gpb":"badoo.bma.ProfileField","id":"languages","type":11,"name":"Langues","display_value":"Fran\u00e7ais"}],"my_vote":1,"their_vote":1,"match_message":"","is_match":false,"is_blocked":false,"is_favourite":false,"unread_messages_count":0,"allow_add_to_favourites":true,"allow_voting":false,"has_mobile_app":true,"allow_send_gift":true,"is_inapp_promo_partner":false,"type":0},"encrypted_im_writing":"rlhGw3BYDDIe8g8R6GZ496Aobsot.Ag96LhDB5nPasPA47bzvJY9enGSQdhBTZqYn8xYKuQY6dnxiUvoZ5qTUZNko5owdZudI5XNHAIh7wxKgY1Ely7uX4wQmPUh37wFlRpPmjNY9yDJdK7uC4GCTbE92Z3Noqu1gKO1.4At2Ke8t7uR.TDD3A44ncYHlJ8S-bLyj.FCcbg0pQnfd1dAdZDITQ5K9s86m5nXZiRbqYK1RoSF8dEUFSWg3AyZOhW7T6TxrHBawPXnfFj9hjn-USqMkyP2YqzYX2bcVPjTk7pEWmaXTrF7w8uXi9Vxd63VYRs.e1sQbPl8PmCGBhuMPQ66S4m1yE0r","encrypted_comet_url":"\/ecomet\/1\/12\/","promo_banners":[{"$gpb":"badoo.bma.PromoBlock","mssg":"Pour passer un appel vid\u00e9o \u00e0 Tsilavina Aron, t\u00e9l\u00e9charge l\'appli Badoo pour Android ou iOS","action":"","header":"Appels vid\u00e9o","ok_action":40,"other_text":"","promo_block_type":10017,"promo_block_position":18,"credits_cost":"","unique_id":"3f7e8c2b80da63517ed6fe4b8e9200c7","buttons":[{"$gpb":"badoo.bma.CallToAction","action":63,"redirect_page":{"$gpb":"badoo.bma.RedirectPage","redirect_page":73,"url":"http:\/\/app.appsflyer.com\/id351331194?pid=videochat&c=entrypoint"},"type":12},{"$gpb":"badoo.bma.CallToAction","action":63,"redirect_page":{"$gpb":"badoo.bma.RedirectPage","redirect_page":73,"url":"http:\/\/app.appsflyer.com\/com.badoo.mobile?pid=videochat&c=entrypoint"},"type":13}],"stats_required":[1]},{"$gpb":"badoo.bma.PromoBlock","mssg":"Attends qu\'elle te r\u00e9ponde ou essaie d\'attirer son attention avec un petit cadeau\u00a0:","action":"Offrir un cadeau","header":"","ok_action":4,"other_text":"","pictures":[{"$gpb":"badoo.bma.ApplicationFeaturePicture","display_images":"\/\/pd2eu.badoocdn.com\/big\/assets\/gifts3\/thumb\/web\/standard\/sz___size__\/candle.png"}],"ok_payment_product_type":5,"promo_block_type":24,"promo_block_position":6,"credits_cost":"","unique_id":"ec05cb4a6aaa825045ff219bf7f67849","stats_required":[1,2]}],"initial_chat_screen":{"$gpb":"badoo.bma.InitialChatScreen","type":7,"title":"","subtitle":"Elle a envie de tchatter","message":"Pourquoi ne pas lui envoyer un petit message\u00a0?","user":{"$gpb":"badoo.bma.User","user_id":"755477482","client_source":10,"access_level":10}},"read_messages_timestamp":1577898824,"is_not_interested":false},"filters_config":{"$gpb":"badoo.bma.CombinedFolderFiltersConfig","filters":[{"$gpb":"badoo.bma.CombinedFolderFilter","id":2,"name":"Tous","type":1,"folder":0},{"$gpb":"badoo.bma.CombinedFolderFilter","id":8,"name":"En ligne","type":1,"folder":0,"filter":0},{"$gpb":"badoo.bma.CombinedFolderFilter","id":4,"name":"Favoris","type":1,"folder":0,"filter":6},{"$gpb":"badoo.bma.CombinedFolderFilter","id":5,"name":"Matchs","type":1,"folder":0,"filter":7},{"$gpb":"badoo.bma.CombinedFolderFilter","id":1,"name":"Rechercher","type":2,"folder":0}],"current_filter":2}},"message_type":469}],"responses_count":1,"is_background":false,"vhost":""}',true);


        $discussions = array();


        foreach ($data['body'][0]['client_open_messenger']['contacts']['section'] as $key => $val) {

            if(!isset($val['users'])) {
                continue;
            }


            foreach ($val['users'] as $k => $user) {


                if ($user['is_deleted'] || $user['is_blocked']) {
                    continue;
                }

                // Remove bots
                if(strpos($user['display_message'],'veut tchatter')) {
                    continue;
                }

                $profile = new Profile();
                $profile->setFullName($user['name']);
                $profile->addPicture('https:' . $user['profile_photo']['square_face_url']);
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


    public function getDiscussion(string $discussion_id) : array
    {

        $body = '{"version":1,"message_type":102,"message_id":28,"body":[{"message_type":102,"server_open_chat":{"user_field_filter":{"projection":[200,340,230,640,580,300,860,280,590,591,250,700,762,592,880,582,930,585,583,305,330,763,1422,584,1262],"request_albums":[{"count":10,"offset":1,"album_type":2,"photo_request":{"return_preview_url":true,"return_large_url":true}}]},"chat_instance_id":"'. $discussion_id .'","message_count":50}}],"is_background":false}';

        $data = $this->post('webapi.phtml?SERVER_OPEN_CHAT',$body);

        $this->handleServerError($data);

        $profile = new Profile();
        $profile->setAppId($discussion_id);
        $profile->setApp(self::APP);

        $messages = array();

        if(!isset($data['body'][0]['client_open_chat']['chat_messages'])) {
            return $messages;
        }

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
     * @param $discussionId
     * @param Message $message
     * @return Message
     * @throws \Exception
     */
    public function sendMessage($discussionId, Message $message) : Message
    {

        throw new BadRequestHttpException("Il est impossible d'envoyer des messages sur " . self::APP);

        $body = json_decode('{"version":1,"message_type":104,"message_id":19,"body":[{"message_type":104,"chat_message":{"mssg":"","message_type":1,"stats_data":"1.833","uid":"","from_person_id":"","to_person_id":"","read":false,"chat_block_id":7}}],"is_background":false}',true);

        $body['body'][0]['chat_message'] = array(
            "mssg" => $message->getContent(),
            "message_type" => 1,
            "uid" => '',
            "from_person_id" => $this->user->getBadooUserId(),
            "to_person_id" => $discussionId,
            "read" => false,
        );

        $data = $this->post('/webapi.phtml?SERVER_SEND_CHAT_MESSAGE',$body);

        dump($data);
        die();

        $this->handleServerError($data);

        $message->setAppId($data['body'][0]['chat_message_received']['chat_message']['uid']);
        $message->setApp(self::APP);
        $message->setSentDate($data['body'][0]['chat_message_received']['chat_message']['date_modified']);
        $message->setProfile($this->getProfile());

        return $message;

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
     * @return bool
     */
    public function isConfigured(): bool
    {

        return $this->cookieJar->getCookieByName('s1') != null;

    }


    /**
     * @param array $array
     */
    public function setCookieJar(array $array = array()): void
    {
        parent::setCookieJar($array);

        if($this->cookieJar->getCookieByName('s1')) {
            $this->headers['X-Session-id'] = $this->cookieJar->getCookieByName('s1')->getValue();
        }


    }

}