<?php


namespace App\Services;

use App\Entity\Discussion;
use App\Entity\Match;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;

class OkCupidService extends APIService
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
            'base_uri' => 'https://www.okcupid.com',
            'headers' => $this->headers,
            'cookies' => $this->cookieJar,
        ));

        $this->setUser($security->getUser());

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
     * @param User $user
     */
    public function setUser(User $user = null)
    {

        $this->user = $user;
        if($this->user) {
            $this->headers = array_merge($this->headers,array(
                'Authorization' => 'Bearer ' . $this->user->getOkcupidToken()
            ));
        } else {
            unset($this->headers['Authorization']);
        }
    }




    /**
     * @return array
     * @throws \Exception
     */
    public function getMatches() : array
    {

        throw new BadRequestHttpException("This app is not configured yet");

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
        throw new BadRequestHttpException("This app is not configured yet");
    }

    /**
     * @param Profile $profile
     *
     * @return Match
     */
    public function pass(Profile $profile) : Match
    {
        throw new BadRequestHttpException("This app is not configured yet");
    }


    /**
     *
     */
    public function getMessageList()
    {



        $data = array (
            'operationName' => 'getInboxPage',
            'variables' =>
                array (
                    'userid' => '14492524571012595293',
                    'conversationsFilter' => 'INCOMING',
                ),
            'query' => 'fragment ArchivedConversationCount on User {
  conversationCounts {
    archived
    __typename
  }
  __typename
}

fragment LikesMutual on User {
  likesMutual(after: $matchesAfter) {
    data {
      senderLikeTime
      targetLikeTime
      targetLikeViaSpotlight
      senderMessageTime
      targetMessageTime
      user {
        id
        displayname
        username
        age
        primaryImage {
          square225
          __typename
        }
        location {
          summary
          __typename
        }
        isOnline
        __typename
      }
      __typename
    }
    pageInfo {
      hasMore
      after
      __typename
    }
    __typename
  }
  __typename
}

fragment Conversations on User {
  conversations(filter: $conversationsFilter, after: $conversationsAfter) {
    data {
      threadid
      time
      isUnread
      sentTime
      receivedTime
      correspondent {
        senderLikeTime
        targetLikeTime
        targetLikeViaSpotlight
        senderMessageTime
        targetMessageTime
        matchPercent
        user {
          id
          displayname
          username
          age
          isOnline
          primaryImage {
            square225
            __typename
          }
          __typename
        }
        __typename
      }
      snippet {
        text
        sender {
          id
          __typename
        }
        __typename
      }
      __typename
    }
    pageInfo {
      hasMore
      after
      total
      __typename
    }
    __typename
  }
  __typename
}

query getInboxPage($userid: String!, $matchesAfter: String, $conversationsFilter: ConversationFilter!, $conversationsAfter: String) {
  user(id: $userid) {
    id
    ...LikesMutual
    ...Conversations
    ...ArchivedConversationCount
    __typename
  }
}
',
        );

        return array();


        $response = $this->rawPostFull('/graphql',$data);


        dump($response->getHeaders());
        die();

        $discussions = array();


        return $discussions;
    }


    /**
     * @return User
     */
    public function disconnect(): User
    {
        $this->user->setOkcupidToken(null);

        $this->em->persist($this->user);
        $this->em->flush();

        $this->setUser($this->user);

        return $this->user;
    }


    public function validateLogin(array $credentials = array()): bool
    {
        // TODO: Implement validateLogin() method.
    }

    /**
     * @return bool
     */
    public function isConfigured(): bool
    {

        return $this->getUser()->getOkcupidToken() != null;

    }


}