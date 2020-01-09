<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Match;
use App\Entity\Message;
use App\Entity\Profile;
use App\Entity\User;
use App\Services\APIService;
use App\Services\BadooService;
use App\Services\BumbleService;
use App\Services\OkCupidService;
use App\Services\TiiltService;
use App\Services\TinderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;


/**
 *
 * @Route("/api/{app}/")
 *
 * Class ApIController
 * @package App\Controller
 */
class ApiController extends AbstractController
{


    /**
     * @var APIService[]
     */
    private $services = array();


    protected $kernel;

    /**
     * ApiController constructor.
     * @param TinderService $tinderService
     * @param BumbleService $bumbleService
     * @param KernelInterface $kernel
     */
    public function __construct(TinderService $tinderService,BumbleService $bumbleService,BadooService $badooService,KernelInterface $kernel)
    {
        $this->services[TinderService::APP] = $tinderService;
        $this->services[BumbleService::APP] = $bumbleService;
        $this->services[BadooService::APP] = $badooService;
        $this->kernel = $kernel;


        foreach ($this->services as $service) {
            $service->setCookieJar($this->getCookies($service));
        }

    }

    /**
     *
     * @Route("matches", methods={"GET"})
     *
     * @param string $app
     * @return JsonResponse
     */
    public function matchesAction(string $app)
    {


        $matches = array();
        if($app === 'all') {
            $services = $this->getConfiguredServices('getMatches');

            foreach ($services as $service) {
                try {
                    $matches = array_merge($matches, $service->getMatches());
                }catch (\Exception $exception) {
                    //@Do something
                }
            }

            shuffle($matches);


        } else {

            $service = $this->getService($app);

            if(!$service->isConfigured()) {
                throw new NotAcceptableHttpException("Service is not configured for current user");
            }

            $matches = $service->getMatches();
        }

        if(!$matches) {
            throw new NotFoundHttpException("Aucun profil trouvé");
        }

        return new JsonResponse($matches);

    }


   /**
     *
     * @Route("matches/pending", methods={"GET"})
     *
     * @param string $app
     * @return JsonResponse
     */
    public function matchesPendingAction(string $app)
    {

        $notConfigured = 0;

        $matches = array();
        if($app === 'all') {

            $services = $this->getConfiguredServices('getPendingMatches');

            foreach ($services as $service) {
                try {
                    $matches = array_merge($matches, $service->getPendingMatches());
                }catch (\Exception $exception) {
                    //@Do something
                }
            }

            shuffle($matches);

        } else {

            $service = $this->getService($app);

            if(!$service->isConfigured()) {
                throw new NotAcceptableHttpException("Service is not configured for current user");
            }

            if(!method_exists($service,'getPendingMatches')) {
                throw new BadRequestHttpException("Get pending matches doesn't work yet on app " . $service::APP);
            }

            $matches = $service->getPendingMatches();
        }

        if(!$matches) {
            throw new NotFoundHttpException("Aucun profil trouvé");
        }

        return new JsonResponse($matches);

    }



    /**
     *
     * @Route("location", methods={"POST"})
     *
     * @param Request $request
     * @param string $app
     * @return JsonResponse
     */
    public function locationUpdateAction(Request $request,string $app)
    {

        $location = json_decode($request->getContent(),true);

        if(!$location) {
            throw new BadRequestHttpException("Location must be defined");
        }

        $notConfigured = 0;

        $matches = array();
        if($app === 'all') {

            $services = $this->getConfiguredServices('updateLocation');

            foreach ($services as $service) {
                try {
                    $service->updateLocation($location);
                }catch (\Exception $exception) {
                }
            }

        } else {

            $service = $this->getService($app);

            if(!$service->isConfigured()) {
                throw new NotAcceptableHttpException("Service is not configured for current user");
            }

            $matches = $service->updateLocation($location);
        }


        return new JsonResponse(array("success" => "Votre localisation a bien été mise à jour"));

    }


    /**
     * @param null $method
     * @return APIService[]
     */
    protected function getConfiguredServices($method = null) : array
    {
        $services = array();
        foreach ($this->services as $service) {
            if(!$service->isConfigured()) {
                continue;
            }

            if($method && !method_exists($service,$method)) {
                continue;
            }

            $services[] = $service;

        }

        if(empty($services)) {
            throw new NotAcceptableHttpException("You need to configure at least one service for current user");
        }

        return $services;
    }



    /**
     *
     * @Route("location", methods={"GET"})
     *
     * @param Request $request
     * @param string $app
     * @return JsonResponse
     */
    public function locationAction(Request $request,string $app)
    {


        $location = array();

        if($app === 'all') {

            $services = $this->getConfiguredServices('getCurrentLocation');

            foreach ($services as $service) {
                try {
                    $location = $service->getCurrentLocation();
                    break;
                }catch (\Exception $exception) {

                }
            }

        } else {

            $service = $this->getService($app);

            if(!$service->isConfigured()) {
                throw new NotAcceptableHttpException("Service is not configured for current user");
            }

            $location = $service->getCurrentLocation();
        }


        return new JsonResponse($location);

    }


    /**
     *
     * @param Request $request
     * @param string $app
     *
     * @Route("token/refresh", methods={"GET"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function refreshTokenAction(Request $request,string $app)
    {

        $service = $this->getService($app);

        if(!method_exists($service,'refreshToken')) {
            throw new BadRequestHttpException("Refresh token is not available in app $app");
        }

        /** @var User $user */
        $user = $service->refreshToken();

        return new JsonResponse($user);
    }



    /**
     *
     * @param Request $request
     * @param string $app
     *
     * @Route("messages", methods={"GET"})
     *
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function messagesAction(Request $request,string $app)
    {

        $notConfigured = 0;

        $discussions = array();

        if($app === 'all') {

            $d = array();
            $maxLength = 0;

            foreach ($this->services as $service) {
                if($service->isConfigured() && method_exists($service,'getMessageList')) {
                    try {
                        $msgs = $service->getMessageList();
                        $maxLength = max($maxLength,count($msgs));
                        $d[] = $msgs;
                    }catch (\Exception $e) {
                        // @Do nothing,
                    }
                } else {
                    $notConfigured++;
                }
            }

            if(count($this->services) === $notConfigured) {
                throw new NotAcceptableHttpException("You need to configure at least one service for current user");
            }


            for($i = 0;$i < $maxLength;$i++) {
                foreach ($d as $value) {
                    if(isset($value[$i])) {
                        $discussions[] = $value[$i];
                    }
                }
            }

        } else {

            $service = $this->getService($app);


            if(!method_exists($service,'getMessageList')) {
                throw new NotAcceptableHttpException("Messages List is not available yey in app $app");
            }

            $discussions = $service->getMessageList();
        }

        if(!$discussions) {
            throw new NotFoundHttpException("Aucune discussion trouvée");
        }

        return new JsonResponse($discussions);

    }



    /**
     *
     * @param Request $request
     * @param string $app
     * @param string $discussion
     *
     * @Route("messages/{discussion}", methods={"GET"})
     *
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function messageAction(Request $request,string $app,string $discussion)
    {

        $service = $this->getService($app);

        $messages = $service->getDiscussion($discussion);

        if(!$messages) {
            throw new NotFoundHttpException("Aucun message trouvé");
        }

        return new JsonResponse($messages);

    }


    /**
     *
     * @param Request $request
     * @param string $app
     * @param string $discussion
     *
     * @Route("messages/{discussion}", methods={"POST"})
     *
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function postMessageAction(Request $request,string $app,string $discussion)
    {

        $service = $this->getService($app);

        if(!method_exists($service,'sendMessage')) {
            throw new BadRequestHttpException("Send message is not configured yet for app $app");
        }

        $body = json_decode($request->getContent(),true);


        if(!$body) {
            throw new BadRequestHttpException("Message must be defined");
        }

        try {
            $message = new Message($body);
        }catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $messages = $service->sendMessage($discussion,$message);

        if(!$messages) {
            throw new NotFoundHttpException("Aucun message trouvé");
        }

        return new JsonResponse($messages);

    }




    /**
     *
     * @param Request $request
     * @param string $app
     *
     * @Route("login" , methods={"POST"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function loginAction(Request $request,string $app)
    {

        $credentials = json_decode($request->getContent(),true);

        $service = $this->getService($app);

        if(!$credentials) {
            throw new BadRequestHttpException("Credentials are required");
        }


        $service->login($credentials);

        $this->saveCookie($service);

        return new JsonResponse($service->getUser());
    }



    /**
     *
     * @param Request $request
     * @param string $app
     *
     * @Route("logout" , methods={"POST"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function logoutAction(Request $request,string $app)
    {

        $service = $this->getService($app);

        $service->disconnect();
        $this->deleteCookie($service);

        return new JsonResponse($service->getUser());
    }



    /**
     *
     * @param Request $request
     * @param string $app
     *
     * @Route("login/validate" , methods={"POST"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function loginValidateAction(Request $request,string $app)
    {

        $credentials = json_decode($request->getContent(),true);

        $service = $this->getService($app);

        $service->validateLogin($credentials);

        return new JsonResponse($service->getUser());
    }

    /**
     *
     * @param Request $request
     * @param string $app
     *
     * @Route("like" , methods={"POST"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function likeAction(Request $request,string $app)
    {

        $profile = $this->parseBodyContent($request);

        $service = $this->getService($profile->getApp());

        $data = $service->like($profile);

        return new JsonResponse($data);
    }



    /**
     *
     * @param Request $request
     * @param string $app
     *
     * @Route("favorites" , methods={"POST"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function favoriteAction(Request $request)
    {

        $profile = $this->parseBodyContent($request);

        $profile->setOwner($this->getUser());
        $profile->setIsFavorite(true);


        /** @var Profile $previous */
        $previous = $this->getDoctrine()->getRepository(Profile::class)->findOneBy(array(
            'owner' => $this->getUser(),
            'app' => $profile->getApp(),
            'appId' => $profile->getAppId(),
        ));

        if($previous) {
            if($previous->isFavorite()) {
                throw new BadRequestHttpException("Profile is already a favorite");
            } else {
                $profile = $previous;
                $profile->setIsFavorite(true);
            }
        }


        $em = $this->getDoctrine()->getManager();

        $em->persist($profile);
        $em->flush();

        return new JsonResponse($profile);
    }

    /**
     *
     * @Route("favorites/{profile}" , methods={"DELETE"})
     *
     * @param Request $request
     *
     * @param Profile $profile
     *
     * @return JsonResponse
     */
    public function deleteFavoriteAction(Request $request,Profile $profile)
    {

        if($profile->getOwner() !== $this->getUser()) {
            throw new AccessDeniedHttpException("Access Denied");
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($profile);
        $em->flush();

        return new JsonResponse($profile);
    }


    /**
     *
     * @param Request $request
     * @param string $app
     *
     * @Route("favorites" , methods={"GET"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function getFavoritesAction(Request $request,string $app)
    {

        $filter = array(
            'owner' => $this->getUser(),
            'isFavorite' => true,
        );

        if($app != 'all') {
            $filter['app'] = $app;
        }

        $em = $this->getDoctrine()->getManager();


        /** @var Profile[] $profiles */
        $profiles = $em->getRepository(Profile::class)
            ->findBy($filter);


        return new JsonResponse($profiles);
    }



    /**
     *
     * @param Request $request
     *
     * @Route("dislike" , methods={"POST"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function passAction(Request $request)
    {

        $profile = $this->parseBodyContent($request);

        $service = $this->getService($profile->getApp());

        $data = $service->pass($profile);

        return new JsonResponse($data);
    }



    /**
     *
     * @param Request $request
     *
     * @Route("superlike", methods={"POST"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function superLikeAction(Request $request)
    {

        $profile = $this->parseBodyContent($request);

        $service = $this->getService($profile->getApp());

        $data = $service->superLike($profile);

        $em = $this->getDoctrine()->getManager();


        // Delete the favorite on superlike
        /** @var Profile $profile */
        $profile = $em->getRepository(Profile::class)
            ->findOneBy(array(
                'owner' => $this->getUser(),
                'app' => $profile->getApp(),
                'appId' => $profile->getAppId(),
            ));

        if($profile) {
            $em->remove($profile);
            $em->flush();
        }

        return new JsonResponse($data);
    }


    /**
     *
     * @param Request $request
     *
     * @Route("apps" , methods={"GET"})
     *
     * @return JsonResponse
     * @throws \Exception
     *
     */
    public function appsAction(Request $request)
    {

        $apps = array();

        foreach ($this->services as $service) {
            $apps[] = array(
                'app' => $service::APP,
                'title' => ucfirst($service::APP),
                'img' => $request->getSchemeAndHttpHost() . "/assets/images/" . $service::APP ."_logo.png",
                'isConfigured' => $service->isConfigured(),
            );
        }


        return new JsonResponse($apps);
    }



    /**
     * @param Request $request
     * @return Profile
     */
    protected function parseBodyContent(Request $request) : Profile
    {
        $body = json_decode($request->getContent(),true);


        if(!$body) {
            throw new BadRequestHttpException("Profile must be defined");
        }

        try {
            $profile = new Profile($body);
        }catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $profile;
    }



    /**
     * @param APIService $APIService
     * @return false|int
     */
    protected function saveCookie(APIService $APIService)
    {
        $root = $this->kernel->getCacheDir();

        $cookies = $APIService->getCookieJar()->toArray();

        $user = $APIService->getUser()->getId();

        $app = $APIService::APP;

        $root = $root . '/cookies';

        if(!is_dir($root)) {
            mkdir($root);
        }

        $root = $root . "/$app";
        if(!is_dir($root)) {
            mkdir($root);
        }

        return file_put_contents($root .'/'.  "$user.json",json_encode($cookies,JSON_PRETTY_PRINT));

    }

    /**
     * @param APIService $APIService
     * @return false|int
     */
    protected function deleteCookie(APIService $APIService)
    {
        $root = $this->kernel->getCacheDir();

        $cookies = $APIService->getCookieJar()->toArray();

        $user = $APIService->getUser()->getId();

        $app = $APIService::APP;

        $root = $root . '/cookies';

        $file = $root .'/'.  "$user.json";

        if(file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * @param APIService $APIService
     * @return array
     */
    protected function getCookies(APIService $APIService)
    {
        $root = $this->kernel->getCacheDir();

        $user = $APIService->getUser()->getId();

        $app = $APIService::APP;

        $root = $root . '/cookies';

        $root = $root . "/$app";


        $file = $root .'/'.  "$user.json";

        if(!file_exists($file)) {
            return array();
        }

        return json_decode(file_get_contents($file),true);

    }



    /**
     * @param string $app
     * @return APIService
     */
    protected function getService(string $app) : APIService
    {
        if(!isset($this->services[$app])) {
            throw new BadRequestHttpException(sprintf('The app %s is not configured yet',$app));
        }

        return $this->services[$app];

    }


}