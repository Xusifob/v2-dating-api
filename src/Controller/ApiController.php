<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Match;
use App\Entity\Message;
use App\Entity\Profile;
use App\Entity\User;
use App\Services\APIService;
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
     * @param TiiltService $tiiltService
     * @param KernelInterface $kernel
     */
    public function __construct(TinderService $tinderService,BumbleService $bumbleService,TiiltService $tiiltService,KernelInterface $kernel)
    {
        $this->services[TinderService::APP] = $tinderService;
        //   $this->services[OkCupidService::APP] = $okCupidService;
        $this->services[BumbleService::APP] = $bumbleService;
        $this->services[TiiltService::APP] = $tiiltService;
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

        //     return new Response('[{"app":"tinder","fullName":"Sam","bio":"J\u0027adore voyager \ud83c\udf0det \u00e9couter de la musique \ud83d\udc9e\u2764\ufe0f","age":20,"appId":"5e05eab291fd5401004ca991","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5e05eab291fd5401004ca991\/320x400_bbd821ee-0801-4f4b-8f5a-75099eeb20f1.jpg","https:\/\/images-ssl.gotinder.com\/5e05eab291fd5401004ca991\/320x400_252a3937-ca2b-4a70-bfc2-789f4194aea1.jpg","https:\/\/images-ssl.gotinder.com\/5e05eab291fd5401004ca991\/320x400_df8dfde7-0231-4232-a217-55f85c66f853.jpg"],"distance":"10","jobTitle":null,"school":"Conservatoire royal de Li\u00e8ge","attributes":{"s_number":868235953}},{"app":"tinder","fullName":"Astrid","bio":"","age":27,"appId":"5cf3293ceaa9e915008bd5fd","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_711c1c84-5b8e-4feb-903b-5583e0e376a6.jpg","https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_04384321-d1c0-4ea4-99bc-9579d8601602.jpg","https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_a2454b45-4051-4a52-96d2-981bdad4fe7e.jpg","https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_b7b7041f-8d63-4262-91ac-302902950b82.jpg","https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_c2eb35af-fff2-46d2-86fd-447743cc344c.jpg"],"distance":"15","jobTitle":null,"school":"Institut Polytechnique LaSalle Beauvais","attributes":{"s_number":754965240}},{"app":"tinder","fullName":"Pauline","bio":"Ronde et fi\u00e8re de l\u0027\u00eatre \ud83d\ude0d\n\nJe suis HYPER chiante , un peu folle sur les bords , mon rire est HORRIBLE , j\u0027aime bien avoir raison , je parle tout le temps tu pourras pas en placer une ! si je fais tout l\u0027inverse de \u00e7a lorsque je te vois, \u00e9pouse moi!\n\nLes petits plus : un homme barbu\u0026tatou\u00e9 \ud83d\ude0d\n","age":26,"appId":"57100c14cad835811469fe8d","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/57100c14cad835811469fe8d\/320x400_62c8b019-150e-4f12-b1f6-7de193b577ee.jpg","https:\/\/images-ssl.gotinder.com\/57100c14cad835811469fe8d\/320x400_4539f8e0-48e5-4bb4-83ef-e964cf76e055.jpg","https:\/\/images-ssl.gotinder.com\/57100c14cad835811469fe8d\/320x400_5d9ff358-6695-40be-acab-68da8c72af1d.jpg"],"distance":"15","jobTitle":"\u00c9ducatrice sp\u00e9cialis\u00e9e","school":null,"attributes":{"s_number":213000052}},{"app":"tinder","fullName":"Cl\u00e9mence","bio":"J\u2019ai pass\u00e9 beaucoup trop de temps \u00e0 chercher une bio originale","age":22,"appId":"5cabb5f788ecbe15001f2ba3","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5cabb5f788ecbe15001f2ba3\/320x320_48545ee7-0cfb-4012-808b-c9de8d8ae0fc.jpg","https:\/\/images-ssl.gotinder.com\/5cabb5f788ecbe15001f2ba3\/320x400_343b5f5a-48b3-466c-9cef-291478312f17.jpg","https:\/\/images-ssl.gotinder.com\/5cabb5f788ecbe15001f2ba3\/320x320_e5c1ecbe-e2d7-4f10-8530-9af7cdb20e71.jpg","https:\/\/images-ssl.gotinder.com\/5cabb5f788ecbe15001f2ba3\/320x320_a0980c60-dcae-4b08-8014-a46396bd591c.jpg"],"distance":"13","jobTitle":null,"school":null,"attributes":{"s_number":725987014}},{"app":"tinder","fullName":"Maelis","bio":"","age":26,"appId":"5e0601797c1f5901004f4c42","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5e0601797c1f5901004f4c42\/320x400_d2ea5d92-6ffc-4a9a-a321-d25c6e3acc1f.jpg"],"distance":"1","jobTitle":null,"school":"Giraux","attributes":{"s_number":868268556}},{"app":"tinder","fullName":"Laura","bio":"","age":24,"appId":"5ad3950078851f7942ef2081","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5ad3950078851f7942ef2081\/320x400_629f0700-b81b-4735-b6f9-e4a68493e690.jpg"],"distance":"3","jobTitle":null,"school":null,"attributes":{"s_number":528854570}},{"app":"tinder","fullName":"Justine","bio":"","age":25,"appId":"53790511ed157c2235daeb3f","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/53790511ed157c2235daeb3f\/320x400_59e891b6-f7f9-4192-a989-37212e80554d.jpg","https:\/\/images-ssl.gotinder.com\/53790511ed157c2235daeb3f\/320x320_186a1602-c05e-4328-a31d-8f01d90e7790.jpg","https:\/\/images-ssl.gotinder.com\/53790511ed157c2235daeb3f\/320x400_e969e3d1-eda0-4b5c-97d4-d3878c11a79a.jpg"],"distance":"9","jobTitle":null,"school":null,"attributes":{"s_number":45055777}},{"app":"tinder","fullName":"Lea","bio":"\ud83c\udde9\ud83c\uddea \ud83c\uddeb\ud83c\uddf7 \ud83c\udff3\ufe0f\u200d\ud83c\udf08","age":20,"appId":"5d658f18744ddb1600f01b1f","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5d658f18744ddb1600f01b1f\/320x400_ac0e8c44-5331-40d9-8764-042607319ee3.jpg","https:\/\/images-ssl.gotinder.com\/5d658f18744ddb1600f01b1f\/320x400_87bf8ace-39aa-4fb6-a9f3-cd3b69812001.jpg"],"distance":"5","jobTitle":null,"school":null,"attributes":{"s_number":803433846}},{"app":"tinder","fullName":"Coralie","bio":"","age":20,"appId":"5e0607734291dd0100260c79","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5e0607734291dd0100260c79\/320x400_ef5a0b73-3113-410c-916a-25f5e7d1a10a.jpg"],"distance":"1","jobTitle":null,"school":null,"attributes":{"s_number":868261427}},{"app":"tinder","fullName":"Alice","bio":"","age":27,"appId":"5cf1c31011d60c150077138f","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5cf1c31011d60c150077138f\/320x400_5c578044-c21c-49ca-b725-af0f0f942b63.jpg","https:\/\/images-ssl.gotinder.com\/5cf1c31011d60c150077138f\/320x400_35489e5f-73ed-4aaf-8d0c-f2b875f59d0c.jpg","https:\/\/images-ssl.gotinder.com\/5cf1c31011d60c150077138f\/320x400_c910a670-ca20-4812-8898-2874fc2370d1.jpg","https:\/\/images-ssl.gotinder.com\/5cf1c31011d60c150077138f\/320x400_8c892a71-a06c-4ca6-872b-af7bcb570fc9.jpg"],"distance":"15","jobTitle":null,"school":"Universit\u00e9 Lille 2 - Droit et Sant\u00e9","attributes":{"s_number":754399039}},{"app":"tinder","fullName":"Chlo\u00e9","bio":"\ud83d\udccdCalais\/Lille\n\u26f7\ud83c\udfa7\ud83c\udfac\ud83d\udcf7\ud83d\udecd\ud83c\udf7b\ud83c\udf2f","age":20,"appId":"550ab730d390417d6764e337","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x320_b53351b0-b9fe-44aa-8f05-90c0f7133ff7.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_9dc25489-0533-4046-80ce-ce4eca9730e0.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_579158ef-1647-42bb-8d41-a2876ef3fa9f.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_9503f575-1ae8-4cf7-a298-69086635879c.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_3222089b-5b84-4646-bea8-1f053ea94b05.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_ac30f3c2-73cb-4d18-855c-1f3e441a4fe5.jpg"],"distance":"13","jobTitle":null,"school":"Universit\u00e9 Lille 3","attributes":{"s_number":115525413}},{"app":"tinder","fullName":"Sarah","bio":"\ud83c\udfc0\ud83d\udc84\u2600\ufe0f\ud83c\udf79\ud83c\udf89\ud83c\uddeb\ud83c\uddf7\ud83c\uddf2\ud83c\udde6","age":23,"appId":"5c9562cc5c2bbe1400bfed87","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5c9562cc5c2bbe1400bfed87\/320x320_a00b7f16-8444-4b56-8df3-c5475295f8b2.jpg","https:\/\/images-ssl.gotinder.com\/5c9562cc5c2bbe1400bfed87\/320x320_3a4c826b-b123-4eb3-8c69-3e3981e9cf6d.jpg","https:\/\/images-ssl.gotinder.com\/5c9562cc5c2bbe1400bfed87\/320x400_90119e15-10e5-4178-b0a1-f23e05e39fe1.jpg","https:\/\/images-ssl.gotinder.com\/5c9562cc5c2bbe1400bfed87\/320x320_1a040348-5f9b-4afb-8ff6-8ffcc2ab9b9c.jpg","https:\/\/images-ssl.gotinder.com\/5c9562cc5c2bbe1400bfed87\/320x400_fd38a9aa-eb58-4d70-8b11-e6fa4c36900a.jpg","https:\/\/images-ssl.gotinder.com\/5c9562cc5c2bbe1400bfed87\/320x400_819fce7f-bed7-49c7-a746-a0a81a732b3b.jpg"],"distance":"1","jobTitle":null,"school":null,"attributes":{"s_number":717343509}},{"app":"tinder","fullName":"Paule","bio":"\u00c0 la droite de ma droite, de la droite de ma droite, ma naissance.\n\n                     \ud83d\udd0a\u26fa\ud83c\udf7b\ud83c\udf08\u270f\ufe0f\ud83c\udfaa\ud83c\udf40\ud83e\udd38\ud83c\udfff","age":23,"appId":"5c77bf87dc78bd11005301a7","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5c77bf87dc78bd11005301a7\/320x400_f02884f0-80fb-473b-9187-d756e37ced49.jpg","https:\/\/images-ssl.gotinder.com\/5c77bf87dc78bd11005301a7\/320x400_38096094-6eeb-44c1-84fc-dfad788b412b.jpg","https:\/\/images-ssl.gotinder.com\/5c77bf87dc78bd11005301a7\/320x400_65a8fdaa-71bd-4a2c-8f9f-e77c43779502.jpg","https:\/\/images-ssl.gotinder.com\/5c77bf87dc78bd11005301a7\/320x400_1df04335-50e1-4cc6-abdd-243c06bcbf40.jpg"],"distance":"9","jobTitle":null,"school":null,"attributes":{"s_number":705000370}},{"app":"tinder","fullName":"No\u00e9mie","bio":"Petite, chiante et blonde \ud83d\udc71\u200d\u2640\ufe0f = combo gagnant","age":20,"appId":"5dfe8ddac3206601008bd9f7","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5dfe8ddac3206601008bd9f7\/320x400_896fb604-5132-440c-bf26-638dc9718ac4.jpg","https:\/\/images-ssl.gotinder.com\/5dfe8ddac3206601008bd9f7\/320x400_9b1fe97b-6a6d-413f-b2d9-b0c8a401b1d5.jpg","https:\/\/images-ssl.gotinder.com\/5dfe8ddac3206601008bd9f7\/320x400_6ef3f37e-9d8b-4c69-847e-6e063fa6a1e5.jpg","https:\/\/images-ssl.gotinder.com\/5dfe8ddac3206601008bd9f7\/320x400_9839de90-419e-45bf-bfd1-b27c9f74bb70.jpg","https:\/\/images-ssl.gotinder.com\/5dfe8ddac3206601008bd9f7\/320x400_9e702ae2-e40b-4808-80e2-46a9bb001538.jpg"],"distance":"1","jobTitle":null,"school":null,"attributes":{"s_number":865440537}}]');
        //   return new Response('[{"app":"tinder","fullName":"Audrey","bio":"Hey,\n\nJ\u0027aime beaucoup la culture asiatique, je serais donc tr\u00e8s heureuse de discuter avec des gens ayant cette m\u00eame passion.\n\nI can speak English too \ud83d\ude0a\n\n 1m68","age":26,"appId":"5df64e133a622f010060d9af","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5df64e133a622f010060d9af\/320x400_21bad623-cf24-41b2-b4c3-b7cdff9132d0.jpg","https:\/\/images-ssl.gotinder.com\/5df64e133a622f010060d9af\/320x400_b7033fd3-a42c-49b9-ba2c-98d3827a670a.jpg","https:\/\/images-ssl.gotinder.com\/5df64e133a622f010060d9af\/320x400_0d6ad9d0-e51d-4616-9319-6936882d48ba.jpg","https:\/\/images-ssl.gotinder.com\/5df64e133a622f010060d9af\/320x400_52f60436-3649-4437-882c-477355860710.jpg","https:\/\/images-ssl.gotinder.com\/5df64e133a622f010060d9af\/320x400_bd7c0b0f-8400-48ee-9bd5-9cca3c801bc3.jpg"],"distance":"13km","jobTitle":null,"school":null,"attributes":{"s_number":862341691}},{"app":"tinder","fullName":"AzL\u00e9","bio":"","age":24,"appId":"5d9919c2be65990100369acd","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_40c19bcd-f955-4e27-bb8a-ded3226bf2c4.jpg","https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_5c19f777-5cec-4a72-a74a-e1dc43cac7dd.jpg","https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_1acb7f04-2fb7-486c-8b54-c0d6c7c67b51.jpg","https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_dc1345cd-cf2a-42e7-92a3-72a3d1ac52d1.jpg","https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_79b5a6a5-f313-408d-9748-e43e8152039a.jpg","https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_269d55a8-380c-4a31-bfbc-19b98c1a6918.jpg","https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_a52fef46-621f-40b4-8e04-1ee1412bcd56.jpg","https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_0aa8bda7-0c19-465a-9c09-2f86e244587a.jpg","https:\/\/images-ssl.gotinder.com\/5d9919c2be65990100369acd\/320x400_d7461a89-4283-43b5-9937-c1ee40223124.jpg"],"distance":"13km","jobTitle":null,"school":null,"attributes":{"s_number":825144879}},{"app":"tiilt","fullName":"Carolline","bio":null,"age":36,"appId":"5429483","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6811\/021bccbae0b652aad73c78a59c8b45d5.jpg"],"distance":"Paris","jobTitle":null,"school":null,"attributes":[]},{"app":"bumble","fullName":"Camille","bio":"Emploi : Interne En Medecine chez AP HP \n\rEmplacement : Merris, Nord\n\u00e0 80 km \n\r\u00c0 propos de moi :  \n\rEst-ce que vous faites du sport\u00a0? : Souvent \n\rNiveau d\u0027\u00e9tudes : Dipl\u00f4me universitaire \n\rEst-ce que vous buvez\u00a0? : \u00c0 l\u0027occasion \n\rEst-ce que vous fumez\u00a0? : \u00c0 l\u0027occasion \n\rAvez-vous des animaux domestiques\u00a0? : Aucun \n\rQue recherchez-vous\u00a0? : Je ne sais pas encore \n\rVoulez-vous avoir des enfants\u00a0? : J\u0027aimerais en avoir \n\rQuel est votre signe astrologique\u00a0? : Poissons \n\r","age":26,"appId":"obff5b362880e6595042b1238310bb33d05669ab8f9ba154f","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=z2BLZyhvaASIDhqK9Hgu4NDm1okhINT6F100tnW4zdwt63hVpAbamCNIvflusFZ6AOmHbb5Tb8zbFmAnxHFZbd-0VGnlAgz9wxiQTrmvnFPBhGOVA403S5u33kJHHtkST-NRj4XtiZtMdy7SNl0VJ.VbjaUISEERxk6uJgLGtWH3vl.f9kEGMC6FZbcD.pANtDLPgIMyMY1ClazZg7dG5K.rAk3Y2Zw01Rkw6e-7WFUS4bvHIq9MdA\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=353x570","https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=itYwyOqxlzdIq2gbayzXlRBo.qq8CQ0XZkzG-Vm5NuyTmACGgw1Yy3-ygZq2sC4DGCkj60PNPHp9CDjp139s9cxzBfkWPfFFFahJpmiNGVcteodS4eMmePR0wsPo5YHB7C2l1WYLs3hfT3pe8hPcHw0y.yEfibRy4dOWIuOVD8eNdkt7hJSgggjzQyeK1Axtu7Z2Ls2AJZuA5NOdhWLbkAZn7K.za8LOaPiK-enNOhuv-JwyR5B7-g\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1323","https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=Gz2tYuxUabkWYhRYbbKrYTRAjZ7cLc3fk1TiojQJBngxO14i0sEX.nfAHlO9.4VM4jwCZ8brHoGwgPDmrLVo22EnqyhCJdISf-iCJRAVb0PTJvPE912kIQgAxlFAGqE8EUhm2N-5kL9tFOwHW0-Sy.Evfi-NNT3qwZbE9tgIjYkmtAKRuIa8FuSNQWxztPoeDcZ8OiLxuzVusNbv1G0bePQ2.kjdq625A1Oi8EM2uStqGUaOeQB9Ug\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=673x1257","https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=cpOpaWpieqtDNRKc1tw5ooaNffadbhjMd61R3UJiNhLm8pHSuDUc0q5BRV2H3DqjpzhEog0I2KYNz0ImJ1wz0RLWiW3k8h-LEbnv4FW5reZOEUkPHLKR4dx-cyIUY6evaocKsnXf3FtFI0soqR25JSUvypIECIxxXqAh5oWUUaGZsurO6WDu5kLijb.J7OFw9.9ZafZhZQhofeyURr7IuEISgm21MbB6yEwjEbSoY7hZpd8qi8-Ikw\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1204"],"distance":"\u00e0 80 km","jobTitle":"Interne En Medecine","school":null,"attributes":[]},{"app":"tiilt","fullName":"Angeli","bio":null,"age":34,"appId":"5429468","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6811\/97aa06c5d0f88277ec2d195df9ab42ab.jpg"],"distance":"Allamps","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Alice","bio":"","age":27,"appId":"5cf1c31011d60c150077138f","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5cf1c31011d60c150077138f\/320x400_5c578044-c21c-49ca-b725-af0f0f942b63.jpg","https:\/\/images-ssl.gotinder.com\/5cf1c31011d60c150077138f\/320x400_35489e5f-73ed-4aaf-8d0c-f2b875f59d0c.jpg","https:\/\/images-ssl.gotinder.com\/5cf1c31011d60c150077138f\/320x400_c910a670-ca20-4812-8898-2874fc2370d1.jpg","https:\/\/images-ssl.gotinder.com\/5cf1c31011d60c150077138f\/320x400_8c892a71-a06c-4ca6-872b-af7bcb570fc9.jpg"],"distance":"15km","jobTitle":null,"school":"Universit\u00e9 Lille 2 - Droit et Sant\u00e9","attributes":{"s_number":754923293}},{"app":"tiilt","fullName":"oeillet60","bio":null,"age":60,"appId":"2368450","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/avatar3\/2.jpg"],"distance":"Saint-just-en-chauss\u00e9e","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"catherine","bio":null,"age":36,"appId":"5429449","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6811\/40055eaa9a574741117d513451260859.jpg"],"distance":"Bouqueval","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Mathilde","bio":"Para\u00eetrait-il que j\u0027aime la mer !? \ud83d\ude09","age":24,"appId":"54aed62db559f38e11ae0171","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/54aed62db559f38e11ae0171\/320x400_13535555-61e0-4b3a-a546-85016a052641.jpg","https:\/\/images-ssl.gotinder.com\/54aed62db559f38e11ae0171\/320x400_917efc15-2c4d-45fc-8ee0-9cc9965b2e7f.jpg","https:\/\/images-ssl.gotinder.com\/54aed62db559f38e11ae0171\/320x400_43025100-a06b-4fb8-ba0a-f27f68b50711.jpg","https:\/\/images-ssl.gotinder.com\/54aed62db559f38e11ae0171\/320x400_7c651819-7167-4d9e-bc6d-69fe560fa618.jpg","https:\/\/images-ssl.gotinder.com\/54aed62db559f38e11ae0171\/320x400_481ca374-7ed1-42ec-bf3e-d91a6bca99e9.jpg","https:\/\/images-ssl.gotinder.com\/54aed62db559f38e11ae0171\/320x400_cee536d5-3929-4e57-832a-81df5350669a.jpg","https:\/\/images-ssl.gotinder.com\/54aed62db559f38e11ae0171\/320x400_b36157d8-cf30-4910-a62c-22503115e5fd.jpg","https:\/\/images-ssl.gotinder.com\/54aed62db559f38e11ae0171\/320x400_b162869c-e735-4a18-9e7c-427bdc057620.jpg"],"distance":"14km","jobTitle":null,"school":"IRTS","attributes":{"s_number":97678702}},{"app":"bumble","fullName":"Valentine","bio":"Emplacement : Honfleur, Calvados \n\r\u00c0 propos de moi :  \n\rEst-ce que vous faites du sport\u00a0? : Parfois \n\rEst-ce que vous buvez\u00a0? : R\u00e9guli\u00e8rement \n\rEst-ce que vous fumez\u00a0? : \u00c0 l\u0027occasion \n\rQue recherchez-vous\u00a0? : Je ne sais pas encore \n\rQuel est votre signe astrologique\u00a0? : Taureau \n\rAvez-vous des id\u00e9es politiques\u00a0? : Gauche \n\rSi je n\u0027avais droit qu\u0027\u00e0 3 objets sur une \u00eele d\u00e9serte, ce serait... : assez chiant \n\rMa m\u00e8re dirait que je suis... : sa fille \n\rUn film \u00e0 voir absolument... : Vol au-dessus d\u0027un nid de coucou \n\r","age":24,"appId":"obff5b362880e6595f2b4acb4e8aae3da71d808e1e498d58a","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p13\/hidden?euri=9hbkuEsgIePMIv2rpLufV5AR45rQjkNejHtslDWM0Q5o4V.0WOLGctpZxyz9LvL.17bxD9ttl7zuReIvgY53mApT7yGKq6S1EUaIUw8kMR7XC608B7y9MGnRJlgy6BlSDqUI-bPC911t2pMJ4vbbV9hcC5XMXA7Rssrmsqbl1GHWYlkuT.lenBhcuvRCySr.fv5zlb-qzAwadGbEJhKc1mC4GSt7SqnKdftxb2C4uoug.T2rSNRurPsUIOKj.y8l\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=883x643","https:\/\/pd1eu.bumbcdn.com\/p13\/hidden?euri=8xAiwkNof4ozVBLC.MIV.v658N-epRqmWGtzFSohZWaUCU.YuaCXHDCgaKhDiMTE70YZqEa-H3iPdMxtLCmu86T6UBQZbQgMgTb0jKjU5fb93A.vymD7dOLjwobBYnAbXlAi9rII64z6YY.mVJW6tdzPBBo167mQRvXaxBsr.3d0MKtBvDf4ZF2ZB7GbRtws5j3vtVGdxMGn-AwGd1jIodQoiHd-c0wiKBecVBraT1f6hTpX91yHs-2cYPtBBPwX\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=643x883","https:\/\/pd1eu.bumbcdn.com\/p13\/hidden?euri=LJ7qSf1VGK8z5m0eooCMBvHoPlEVzy8NpFsfcs-m70EIqycdjIypxPpUYNo46J.Slw6f.odRnbshsmwjD5ciGnn2BWSOPAoyYrw-1nwRy8sancavw9K-Wi-zKLh.0IXCaN4TvnYxt-5z0weZ.iqf6c19iuWvnVtFt5GdpxMfYGaewFlQHxZtMufaoEjjtpfqDZUqTZu3Xd8dpOOOkIlgJMPf6Pud6ut5OgJGFO5HMb-RZGKNNDiZasC0T5EUhUoB\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=883x643"],"distance":"","jobTitle":null,"school":null,"attributes":[]},{"app":"bumble","fullName":"Balkis","bio":"Emploi : Consultant technico fonctionnel chez informatique \n\rEmplacement : Cergy, Val-d\u0027Oise \n\r\u00c0 propos de moi : I am here to meet new people \n\rTaille : 163 cm (5\u0027 4\u0027\u0027) \n\rEst-ce que vous faites du sport\u00a0? : Parfois \n\rNiveau d\u0027\u00e9tudes : Dipl\u00f4me universitaire \n\rEst-ce que vous fumez\u00a0? : Jamais \n\rAvez-vous des animaux domestiques\u00a0? : Aucun \n\rQue recherchez-vous\u00a0? : Relation amoureuse \n\rVoulez-vous avoir des enfants\u00a0? : J\u0027aimerais en avoir \n\rQuel est votre signe astrologique\u00a0? : Poissons \n\rAvez-vous une religion\u00a0? : Autre \n\r","age":27,"appId":"obff5b362880e6595c8c87c4968d675aff2a9a3cf9ef48cbf","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p67\/hidden?euri=ZTyGywdmX2z8hzZwcFpUn-Jq60lwor-.vZCN3wx1pxM.D78svjXGeG7YfUK0hKB.tA69EOQlSpxuwa1t8DGnVQzewjXFpsOnn47DByNyplcjEDSHrTeUiNvBcmxlTcLEVEDTR6nGEJ8YyHruLk874jY.FClokJuYmWgZNT-5LU85GFnUUy4PhIlPYncqberc1ZyTezUpU8QVQYHrJhe8UzbcI0YurWoSTCGdt7y7azClbxb6vlRbkg\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1323x973","https:\/\/pd1eu.bumbcdn.com\/p67\/hidden?euri=oePS3FsN1TTnBxOEkjQeL8A5K-ZpVMQZ0lQjXUR6KmVsWL.5Ci.DB7bmWJGOUaFa89vf4EyVWyhufHwyBInmkXagNBcxAr.ct6zWaEBmoGJEAYcoE8J3ae9XuYvqLdCHBsbzPZFA9CMWsolxXFq64XqptawAPfIhOrf7DD3eYS0TxtEzEuUxuhbB1ofqrbdscqBKpt-FxqhtEtASsZMaOgbnhD55nF2uBGL5UpmclighU5nGHt7UYQ\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=964x1603","https:\/\/pd1eu.bumbcdn.com\/p67\/hidden?euri=IUe7Qp4qN2IhRVGSiofMbSQZ98Nl.g4BHWstGQYr51DsjVjXtgF-sTpw.k5xHdIOhDPNE2tNokz1guTsFOghkAK5asFxELjVyOdzKg5av.v.W8dp2j0wUgL8JiAc26gelehSPnIdeq4gg0yVKvAazjmP5P4B6flOzAq.nCnFqsaHiKmTWMvUQ4WEark7-VIsAXw1TsWo3INNrxgYAs7jBQvb7X9LoXzRGu8IDV7gcBxWHjy8BecUQQ\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1235","https:\/\/pd1eu.bumbcdn.com\/p67\/hidden?euri=813cdECnF7A3ekAeQTaYQg9Cq6l3eSNslDRXUscvY8tFxVpabofTM8-xJy2Hk7f0WrqmpKnkqbdo6YzmOoVSqWQhja24.5F7xsG.5KShs8mF8nffXzHa4fR12YymD8frsdwduuVD3YPKTxeuNF7VKHkLO9-UWAibGjVI--HLTB8UjqSYjXzjDHN.qTbJFHLMPYXlgAT4NS3wR0TzU5tMhCdoZlI0RC6dVfdFCd6w3OHbaSrXg0wmUA\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1323x973"],"distance":"","jobTitle":"Consultant technico fonctionnel","school":null,"attributes":[]},{"app":"tinder","fullName":"Justine","bio":"","age":25,"appId":"53790511ed157c2235daeb3f","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/53790511ed157c2235daeb3f\/320x400_59e891b6-f7f9-4192-a989-37212e80554d.jpg","https:\/\/images-ssl.gotinder.com\/53790511ed157c2235daeb3f\/320x320_186a1602-c05e-4328-a31d-8f01d90e7790.jpg","https:\/\/images-ssl.gotinder.com\/53790511ed157c2235daeb3f\/320x400_e969e3d1-eda0-4b5c-97d4-d3878c11a79a.jpg"],"distance":"9km","jobTitle":null,"school":null,"attributes":{"s_number":44531456}},{"app":"tinder","fullName":"Chlo\u00e9","bio":"Cherche a faire de nouvelles rencontres et plus si il y a un feeling \ud83d\ude09\n\nOn fait un deal si le match est de ton c\u00f4t\u00e9 tu fais le premier pas si il est du mien je le fait ","age":24,"appId":"5826c0ce4ef3de840a57215e","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5826c0ce4ef3de840a57215e\/320x400_a7dd7e65-1c0c-4e62-b40a-3e8aa11dcde0.jpg","https:\/\/images-ssl.gotinder.com\/5826c0ce4ef3de840a57215e\/320x320_1b2712be-9e7b-4af7-8bf7-3492c7778399.jpg","https:\/\/images-ssl.gotinder.com\/5826c0ce4ef3de840a57215e\/320x400_6b6dbd15-58b1-4135-943f-972769678482.jpg","https:\/\/images-ssl.gotinder.com\/5826c0ce4ef3de840a57215e\/320x400_b0d1792c-b57d-459e-8988-b15527e9185a.jpg"],"distance":"8km","jobTitle":"Esth\u00e9ticienne SPA","school":null,"attributes":{"s_number":278949881}},{"app":"tiilt","fullName":"charlline59","bio":null,"age":32,"appId":"3955479","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/5337\/666f800b85040ecccad53c4eeba2f3b8.jpg"],"distance":"Fourmies","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"Marilyn","bio":null,"age":61,"appId":"4629108","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6257\/fa151bf4ea867876b28489b1647d5c96.jpg"],"distance":"Saint-Martin-d\u0027H\u00e8res","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Astrid","bio":"","age":27,"appId":"5cf3293ceaa9e915008bd5fd","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_711c1c84-5b8e-4feb-903b-5583e0e376a6.jpg","https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_b7b7041f-8d63-4262-91ac-302902950b82.jpg","https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_04384321-d1c0-4ea4-99bc-9579d8601602.jpg","https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_a2454b45-4051-4a52-96d2-981bdad4fe7e.jpg","https:\/\/images-ssl.gotinder.com\/5cf3293ceaa9e915008bd5fd\/320x400_c2eb35af-fff2-46d2-86fd-447743cc344c.jpg"],"distance":"15km","jobTitle":null,"school":"Institut Polytechnique LaSalle Beauvais","attributes":{"s_number":754965243}},{"app":"bumble","fullName":"Bruna","bio":"Emploi : Assistante chez Agence Emploi Temporaire \n\rEmplacement : Pr\u00e9cy-sur-Oise, Oise \n\r\u00c0 propos de moi :  \n\rTaille : 160 cm (5\u0027 3\u0027\u0027) \n\rEst-ce que vous faites du sport\u00a0? : Parfois \n\rNiveau d\u0027\u00e9tudes : En \u00e9cole sup\u00e9rieure \n\rEst-ce que vous buvez\u00a0? : Jamais \n\rEst-ce que vous fumez\u00a0? : Jamais \n\rAvez-vous des animaux domestiques\u00a0? : Chats \n\rQue recherchez-vous\u00a0? : Relation amoureuse \n\rVoulez-vous avoir des enfants\u00a0? : J\u0027aimerais en avoir \n\rQuel est votre signe astrologique\u00a0? : Balance \n\rAvez-vous une religion\u00a0? : Christianisme \n\rSi je pouvais me t\u00e9l\u00e9porter quelque part ce week-end, ce serait... : \u00c0 Londres \ud83c\uddec\ud83c\udde7 \n\rUne sortie en bo\u00eete ou Netflix : Netflix et pop-corn \ud83c\udf7f \n\rLes 3 points \u00e0 respecter pour qu\u0027une relation marche bien... : L\u2019honn\u00eatet\u00e9, la gentillesse et le respect \u270a\ud83c\udffc \n\r","age":21,"appId":"obff5b362880e6595b000813496df88afacb4a3eb8c587575","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p88\/hidden?euri=xvt5vDbXK5AfxPgHEL8wKEUPBvIi5LR3chd1RO.rGhPyh4R-zGzqlgvgRX4.L.NTvRTA1JzxvVBRTjgRanCbrlyhYRlC.pi6PX.QET4h5wJBCQDxJjOFmScm9Oa.JmjADi6Tk7b4y3Pd.o1or8gJyFyD6B87nPdS5Db8E8Vjr1WpNYdyjCm0J3b1HjpizMTebfaULwkqr3EMG0nUj.1LSAzypfPZHDIBpN9mp0ANy6-z95kx21-H9A\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1257x673","https:\/\/pd1eu.bumbcdn.com\/p88\/hidden?euri=5fWDDSrFPyxXMfp8-y68vVUL5Jwsy8RgO1dCkjGJdlmajWABQkuZeoSHKTGwMWDo0wsYb0AQmdAE-M1w7DC-5hcOQKfn8D07DFeaNYIZPvwBmdc82lM0YBN7XmO1OHOXtB7ltJdjc2wo5fpdPDrm-ZGbNOWmO3irVtHgp98QZNefmwAX-lzJepNhX-MA.tRqLHEq3vE58lxHHfQjZk0FbpHG4MFsRtWRy8MtVs4EtHdjbGU.uToSQg\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1050x824","https:\/\/pd1eu.bumbcdn.com\/p88\/hidden?euri=6rsakrz5ugto495GwEYsnOSCMqemZQ2N4wCkJ1Y1M4BeZQIxZGS6G5v26fv.L48sVoz8uPm28l4yn.a3ZVlA9hLrPZI0IVZUu.-f52s4O4M2HONxNL6pCEStZAxuniVIBeVWxWlccLQQ5Ti-hU6RApwoLWl9om0CwjsZ0lvN2JeWPJfPea49GF8JW7mtBeL8AAMyj6qgSTP0J.Qfxa1kKbzL7cFs-XvISSDJXTNpJSmAZnnCo7WsjA\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1257x673","https:\/\/pd1eu.bumbcdn.com\/p88\/hidden?euri=aPbt752hw1Gc8r8Shm3b2kQVqqlHaYKFW3MY5CiN2-iEqKvW07wHgZ6CsHjh3mP56-aT2CKVNdIJv8jdoCczymL7LhUlJnoLj6jD7rSENwjpPz.Mv8T9vUjFzjb6FZ382IjhubInj1C-mk-gfmd8FGz8rb8CbH0yliokmU2K3pLi5LnhXnxYZrshPzpXIiIIkNdjGgs8dPJmsRIj2YzxzeBUBZcANrCrcSOGI6O.0fpIe0Wb2XY4tg\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=673x1257","https:\/\/pd1eu.bumbcdn.com\/p88\/hidden?euri=6ByxjERRxDxSwyXlkGbat4xLfJ09XdO-icJspalzl3lmAX1HS48yK-7bWJ2q1bUKERrxop7dSVT68jwFad-REO6QI4G8jXxcDiCLOnzkjcZj.W0SOzNd0AeyL0K-Re6Lq33kBcCiJnwqGWefOiVqOxHfl8f08Qc40WExdms4ja6fZmK5nVFnmjC98H4Dd-gEAelj7x4dpi9oz-zaxv9iHkO1bLFIa9hURNB1bGBWGdf1cn8AwMtNLA\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1603x768","https:\/\/pd1eu.bumbcdn.com\/p88\/hidden?euri=AILg8mcHD2fPRJ20Sw7olX4jB09gKGKGw0xu8axmFyh9wZe.cdoqp.PRTApOcjCHHuOIsAmV7fhnay6v5N9g78.so6FdqMPAyPIYQKC0vQUQ2a5Ssbt-VEU2SEmd9R4K5Mvztb3zfU717Ezag6EiTTLggHJR6aqgcL.KxIp5k0opQWLeENqsVAEiUHHt0eJ14iM8cT3N0qPA4wlIpy0Ck8dsxszz8SLk7i7QouJxOiD4D5pa2lhi6g\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1603x768"],"distance":"","jobTitle":"Assistante","school":null,"attributes":[]},{"app":"tiilt","fullName":"Sabine","bio":null,"age":48,"appId":"5367876","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6807\/d2047b6ca2f59e75676fa2920a19cccd.jpg"],"distance":"Orl\u00e9ans","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Maelis","bio":"","age":26,"appId":"5e0601797c1f5901004f4c42","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5e0601797c1f5901004f4c42\/320x400_d2ea5d92-6ffc-4a9a-a321-d25c6e3acc1f.jpg"],"distance":"1km","jobTitle":null,"school":"Giraux","attributes":{"s_number":868268557}},{"app":"bumble","fullName":"Val\u00e9rie","bio":"\u00c9tudes : ISPN Rouen, 2019 \n\rEmplacement : Saint-\u00c9tienne-du-Rouvray, Seine-Maritime \n\r\u00c0 propos de moi : Un peu timide, je souhaiterais faire de nouvelle rencontre. \n\rEst-ce que vous faites du sport\u00a0? : Presque jamais \n\rEst-ce que vous buvez\u00a0? : \u00c0 l\u0027occasion \n\rEst-ce que vous fumez\u00a0? : Jamais \n\rAvez-vous des animaux domestiques\u00a0? : Aucun \n\rQuel est votre signe astrologique\u00a0? : Lion \n\rPlage ou montagne\u00a0? : Plage \n\rSi je n\u0027avais droit qu\u0027\u00e0 3 objets sur une \u00eele d\u00e9serte, ce serait... : Ma brosse a dent \n\rLa qualit\u00e9 que je pr\u00e9f\u00e8re chez quelqu\u0027un... : Le respect \n\r","age":24,"appId":"obff5b362880e6595feb728a8aa4df97566b323b247bc6457","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p87\/hidden?euri=RCwo09MKn9qGqNed681hpB-HjKTEaDy89OWg3wLpWzpGaCFrSNZkhacYl90DdMZLB6BjKD3I00D8On0SZEenPHWQ5qA.8jZwT7zaWZJyai1CZkA8fgOdwS8IAor-Z0ZQrJ7Fjt0l8r2rMd6rmvwn5PS3Fdho358lWnuH43d9x5POT2t6ULcmSaEuSYRIacjtN4ntVJ2G1uJHeHvwEVR043rNiWVs0GlMyfecHLpcBm0nEtKUsQuC7w\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=868x1603","https:\/\/pd1eu.bumbcdn.com\/p87\/hidden?euri=h9MGXbbyTO3RwguV70sZr1sFyvjScE5ugaLB9xzQ.WfmfQK5Kxb13Y4pgS1Cixe5l0C6EO6ShRY2eKZAXL.CMQCa.RCKI.1SlVQwYmNSQUlTrXx5UpG3lKsJ7Ejw6Wc8aH7tOJxN395fAhDJlm7ceDtYjGe05SfKoeEd9hCwiVF8dELMOobc1RrDxfZIO5lfT3qj3z0ccCymws5Oa-nRr35vaawZyPvniv.UYgHHOmGgzD7mzFox7Q\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=543x1203"],"distance":"","jobTitle":null,"school":"ISPN Rouen","attributes":[]},{"app":"bumble","fullName":"Charlotte","bio":"Emplacement : Montigny, Seine-Maritime \n\r\u00c0 propos de moi :  \n\rTaille : 172 cm \n\rEst-ce que vous faites du sport\u00a0? : Parfois \n\rNiveau d\u0027\u00e9tudes : Dipl\u00f4me universitaire \n\rEst-ce que vous buvez\u00a0? : \u00c0 l\u0027occasion \n\rEst-ce que vous fumez\u00a0? : \u00c0 l\u0027occasion \n\rQuel est votre signe astrologique\u00a0? : Poissons \n\r","age":23,"appId":"obff5b362880e6595e22d63a57e3c20a4c0ac8da16e2ca010","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p84\/hidden?euri=tmmiaonGOoIYB92sIjMavBi3JbIjv36.xeq6s8dKfnef.C3tMPOKit4JC3VZobENXIzIHall3zcbZXW0N90BtaQryrEg.aZezoMLF6ab1aHK.V8-opP-3bQrW2AJ4SG2T4le.B0.rlMHFGTBmRyfmrEotzyF3uyHhRV4ujWZDBj3XGcCbs.2AZM-OEyX6ND9HvNOxzJABJSfvLOclfuCc5xmMd5Frw.HN.rTdB-KyF-lUApxzg.Vsg\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=883x639","https:\/\/pd1eu.bumbcdn.com\/p84\/hidden?euri=O75GShjn-ytsJ9vFCx8FQ0A0j9e6e7g8bQgx72lWbhG.gyBr8iuwjyBHsPprzrA6iJwcGSF1UdPFBX-CStU4NszFwmVxe.ovuZtShFS4dsczytPswlk58U.2zwJTkAXhV0yYmOY1Xf0-2KBx6.pjvhdwnQnCUVOml-KXzFyi24dACq21CJntq0KRpVVjjzbU-CXYdCEz4gyA3.fmdEaCr7IrBbZ.7UACMDLBVqwZAutmmys0AAWlQQ\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1498x973","https:\/\/pd1eu.bumbcdn.com\/p84\/hidden?euri=7AFIbtqnVxSZwbHnxmXhLzU3F1gBrkPV.pcWBTv-jzMTWnXIUGGKfrryJjdXJxa29tHCOdtbQE4sKRvYF0XhG3aA-FRuZrgXeqXhgPu6OmyaMSPdTZOh4FXflV0flkgOZMlB6fLgRQRs9dkdIZxIrxMbYG1BK7UPFjwXYdohdOX10n-mXBfyqfkJV12KKoV5BU1u.tuVcpvs7o1c1Lcj2s-40Wsp.jC5vrTVEIWkki4emdS9o48Edg\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=744x1154"],"distance":"","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"taillot34","bio":null,"age":74,"appId":"4353050","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/5832\/0f7c10fca4295bde278aa75fc5ab4e28.jpg"],"distance":"Montpellier","jobTitle":null,"school":null,"attributes":[]},{"app":"bumble","fullName":"Dalia","bio":"Emplacement : Lille, Nord \n\r\u00c0 propos de moi :  \n\rTaille : 160 cm (5\u0027 3\u0027\u0027) \n\rEst-ce que vous faites du sport\u00a0? : Presque jamais \n\rNiveau d\u0027\u00e9tudes : Niveau bac \n\rEst-ce que vous buvez\u00a0? : \u00c0 l\u0027occasion \n\rEst-ce que vous fumez\u00a0? : Jamais \n\rQue recherchez-vous\u00a0? : Je ne sais pas encore \n\rAvez-vous des id\u00e9es politiques\u00a0? : Gauche \n\r","age":23,"appId":"obff5b362880e6595c097a54f1eb61dc392d159b710e920f5","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p71\/hidden?euri=e5oo0ydOp1MiT5IyWWWYN6RyzQjC20oS.Gu.n48bYs6GLii1eraYj6HwlQW3-.9qIq3h61YkA0AFPV2jjihQRGaF-ZxFFmuqC6Q0sJ0MO3sx8EzIiqsV8uzbE-LxKdZ.o.ysIe7VWxiI3GayGzZWry7kjRae8bh0lW1YyMK0A5wHrHJps.HLl9nggtTiI14DmryDpIGfyP.tYWJbvNr42dOYocFkdjS1FONPTmTWUFxzmszXG2k4cw\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1323","https:\/\/pd1eu.bumbcdn.com\/p71\/hidden?euri=pdVS.zgN7CK-Hz1Fw8jQIMl49LEcHC0u.kUhVdiv8w7KqoevZ2yB9wXqIpfrYXtFQXEdMV.Dfend5sbskXWv1iznt9GqWQbWC4g2j9vj8U.mujlR7TuTYEpGp0RmXgtv1KF.YXW81ig3V1PueuU-xTp9XZGM3GP5fbzKl.aQj5PFlvrqbbqtDA8BO8aurOXpDRceWv5gLK7t0xMgYzHNlxe-6BGb5S.N5D24VckxSp1KMTlVoSSJmA\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1323","https:\/\/pd1eu.bumbcdn.com\/p71\/hidden?euri=ZYjlaV9OdxLuj-.sRKwEJAprieDDPQErf4WkgwPIlFK2w5YE5vUlXYezUxMY.4Y.j1587mkDXbIwLlP4N4H-Xya3eVRaS.tWKLuX-M1IC-LQC57QnEqXTjIg5RO-T9c8G46C2u3qfP2afVdUt9QXfB8UXwxN0u8e6b1sKehHLFf8Hye1ZiQQJY-zkHSOA3hEP-LWHSDv7IpNbuzaOjPqpXcV3HDg.etpLy0qrwv1Frryf7alZeQNbw\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=880x882","https:\/\/pd1eu.bumbcdn.com\/p71\/hidden?euri=rRq8XZaNI0E5hdax9tSH8oKIdSuNuT07OQAU8jVWU6IZEmDuKey6S-kZRbDAOhn0WsZEn5ejr9sOVFBByFD5dmb8gqQKdN9pnETaxdb.AgH.eHgjgee5RByOOPf2kWJnmvygd.68Il1D6W756St7MoD86LHxdP9Q8jDpkQYbJKR5S8.qAyN8VdRKlksEQyDK-No8Rr3yDvzQxstm.e.EXlvelNgZodSTM71Dz-RYmBMTvt.wn449tw\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1497"],"distance":"","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Valentine","bio":"Calais \ud83d\udccd\n\u00c9tudiante \u00c9ducatrice Sp\u00e9cialis\u00e9e sur Saint Omer","age":24,"appId":"5cc498d33a39e316009226cb","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5cc498d33a39e316009226cb\/320x400_f2987aaa-20c4-4737-9fda-4dedcf53f7f3.jpg","https:\/\/images-ssl.gotinder.com\/5cc498d33a39e316009226cb\/320x400_ee97391b-0b31-4a06-b186-e45aa3503db0.jpg","https:\/\/images-ssl.gotinder.com\/5cc498d33a39e316009226cb\/320x400_40b42994-02de-4a29-8f82-1c56bfcc1188.jpg","https:\/\/images-ssl.gotinder.com\/5cc498d33a39e316009226cb\/320x400_f004d37e-8948-4d6e-bb02-25ed0e37041a.jpg"],"distance":"10km","jobTitle":null,"school":"ESTS Saint Omer","attributes":{"s_number":735417617}},{"app":"tiilt","fullName":"Adecouvrircelib92","bio":null,"age":36,"appId":"3034759","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/avatar3\/2.jpg"],"distance":"Asco","jobTitle":null,"school":null,"attributes":[]},{"app":"bumble","fullName":"Yanna","bio":"\u00c9tudes : Etudiante Medecine, 2021 \n\rEmplacement : Verderonne, Oise \n\r\u00c0 propos de moi : \u2600\ufe0f \n\rTaille : 177 cm \n\rEst-ce que vous faites du sport\u00a0? : Parfois \n\rNiveau d\u0027\u00e9tudes : En \u00e9cole sup\u00e9rieure \n\rEst-ce que vous buvez\u00a0? : \u00c0 l\u0027occasion \n\rEst-ce que vous fumez\u00a0? : Jamais \n\rAvez-vous des animaux domestiques\u00a0? : Beaucoup \n\rVoulez-vous avoir des enfants\u00a0? : J\u0027aimerais en avoir \n\rQuel est votre signe astrologique\u00a0? : Cancer \n\rAvez-vous une religion\u00a0? : Christianisme \n\rUne sortie en bo\u00eete ou Netflix : Netflix. \n\rSi je devais manger la m\u00eame chose pour le restant de ma vie, ce serait... : Salmon, any kind of fish really \n\rSi je n\u0027avais droit qu\u0027\u00e0 3 objets sur une \u00eele d\u00e9serte, ce serait... : Books, music, anddd the third I don\u2019t know yet \n\r","age":24,"appId":"obff5b362880e6595981880fc2d5f1df4a51750be31d62912","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p43\/hidden?euri=shnAs6cGj0Ny1HMExjcoHpmDCqT1YUVqjTpA2kwC4cNH0GQO0ETCrqNAW640U2UTI97W36.02uw9WffC2dRj4MRDDZvrbMpUocywC76-Pa0NOaQ3gHDeeNv3tMXcuh28PP23xsEyPEqDe.9ubIupRZbSDjCTjKsh7bEuQI8FRhowQ1VdOPOkm6zuKQymFVNwljFAXBygID.n1eCBGGk0fTniwL-Dc6hPGuBStxH5GvcxaMQARfORKLDid-otkf8i\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=672x859","https:\/\/pd1eu.bumbcdn.com\/p43\/hidden?euri=ND76VF.Q.CYKDYPG7NJ9etQjBZUruY693iwXqwZDt1z7uLi7PknC3jA-yElA1MjgTdHXaziQf.e4Hnhk5BgvMZSnw3ydN05ERYi9PEGCXtE4lS6t1TT.FhszlAD98cBJP6CL9ZUpJk7E6N7cK6G7f4GfyDp5P9aBpKBNisSXlemwnzwEoPp841GGRKJ-JQXzx1CAayaV-1mosQwJPnM4ve2gOPTwhzhOQUj9HSxpxqsHij941zC58hyvR.DrY8tM\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1235","https:\/\/pd1eu.bumbcdn.com\/p43\/hidden?euri=aIjvEVD5woBlDjU99LrNxFoM856.Do1lq9u9k9GrMZmp-jRMhSgqZDA6Rh.5m1ZIR.dCiNFyp57aMLzn96qcckJShQHueu7Vd6p.NAWEP3Lmp-yhBcXJgrKQ4NBhmpCHg5IiKVciM0-CtEHkbE8XR.71Ras9CESr2CMAH6h3w7gUSqu0Pdwj-WPOBXKw4FRgyQfDWfN0p12WkAI0Cf2SaqM2flzr8bSRi0gpHU54QeA0BivJlHTLwNRdh7iEdQh0\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1234","https:\/\/pd1eu.bumbcdn.com\/p43\/hidden?euri=lgRM7pp7XFhTXsWkuUkNcMfwm5DBdhCQ6pst7loQYQXS6idNq7kz37vo..cOoeexsK7eFPrxs7GK7PU.USLmycjrQ1RZbkwkUBt.fmp-iO7Bvb1UQk.8K82vEk8qOHJhLGjLpg3nWqX7soPzLheI8tJahRNcIw8Z2vb8tzjnemjUjXFnD4bXYH9hz8zDC3jZeHz8X6RTAIfwprY4yvakI.ROWqu75X4fkctrxdqz8uszi.KHDLkQ7FA-ipOmUuR5\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=673x673","https:\/\/pd1eu.bumbcdn.com\/p43\/hidden?euri=3EPtol8FTRXJ9kBiFMp5I27uquVH95pZqXSH8GcfXpvbvdSASApn8l--l0Z.jOWe8zm0y9gRXmxr67-Lw8u8Om9o1ajFpHnMwsn6IvNa0PHW1kcdN54MzazCv509DsVfj2yP-FNV.xsV35iWQllQiuNgV.StAgSueYPujac1RTy-clhJ9umJQTWEX5D28E1ShulktFRjht3fqNQeQtLYbc5MJUia6YIzWoJ5DZgOoxbwgWUpi5tilI0SAgvSOh6Y\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=823x1048","https:\/\/pd1eu.bumbcdn.com\/p43\/hidden?euri=cybSNNb7sznrLmjpegdTTCxMHdoQRqy3FrXS-lrpQRDQNWjzaskrLjJIScauc-bnAlmV9j2fCPy5fAmGMg666NL9BNSqiNQbZw0N0a-cAb5Lzj58UDYQ8.UkjDk76tdbEzuiUtrs4EepK7Eo75Q3s.Ga4TJsR.M14dbRQdJoySpu8XgUXViqRIz7M795kTQrJA5WbCaBSIDz-WaJaqMJTU9wq20PP6q7PJIScPEYUAVzf3WwuquVt7LafDDK7YaR\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=673x1257"],"distance":"","jobTitle":null,"school":"Etudiante Medecine","attributes":[]},{"app":"tiilt","fullName":"Louisette","bio":null,"age":65,"appId":"5390431","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6811\/b51af599025aab2498d0cf4cd54047c6.jpg"],"distance":"Harnes","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"Nathou","bio":null,"age":49,"appId":"5429473","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6811\/993ac38e4256aa9f1297fa42a0bfaad9.jpg"],"distance":"Dijon","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Sam","bio":"J\u0027adore voyager \ud83c\udf0det \u00e9couter de la musique \ud83d\udc9e\u2764\ufe0fj\u0027adore faire de nouvelles rencontres \ud83c\udfb6\ud83c\udfb6","age":20,"appId":"5e05eab291fd5401004ca991","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5e05eab291fd5401004ca991\/320x400_bbd821ee-0801-4f4b-8f5a-75099eeb20f1.jpg","https:\/\/images-ssl.gotinder.com\/5e05eab291fd5401004ca991\/320x400_252a3937-ca2b-4a70-bfc2-789f4194aea1.jpg","https:\/\/images-ssl.gotinder.com\/5e05eab291fd5401004ca991\/320x400_df8dfde7-0231-4232-a217-55f85c66f853.jpg"],"distance":"10km","jobTitle":null,"school":"Conservatoire royal de Li\u00e8ge","attributes":{"s_number":868235954}},{"app":"tiilt","fullName":"Coralie","bio":null,"age":36,"appId":"5429476","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/avatar3\/2.jpg"],"distance":"Paris","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"Pauline","bio":null,"age":33,"appId":"5429466","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/avatar3\/2.jpg"],"distance":"Agen","jobTitle":null,"school":null,"attributes":[]},{"app":"bumble","fullName":"Maud","bio":"Emplacement : Lille, Nord \n\r\u00c0 propos de moi :  \n\rTaille : 170 cm (5\u0027 7\u0027\u0027) \n\rNiveau d\u0027\u00e9tudes : Dipl\u00f4me universitaire \n\rEst-ce que vous buvez\u00a0? : \u00c0 l\u0027occasion \n\rEst-ce que vous fumez\u00a0? : Jamais \n\rQuel est votre signe astrologique\u00a0? : B\u00e9lier \n\rAvez-vous une religion\u00a0? : Spiritualit\u00e9 \n\r","age":26,"appId":"obff5b362880e65956a61459e6cf6efef18dac8b6c97e28d3","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p44\/hidden?euri=3EPQdGV3zg.ZdBNTfJ8HqUcM9VyrhCRCgEXCOfoz4nIpd16.wh1aM0t-yHncqHxWcn-a645AB0PEJm21P3gyzVDNzH5zgmOCg2yfP.aJhkn-JDUo2WJ9JciwEyrYx75J11VJDhCGi.7YOrGCIpQTYZU-XeyqrmFj4qwM5Y9ondj.l23igj3G2DtiXaoZvIdNvj7Fdt1BOhPP3iCCb2F-vLOwscWj-7tyErNbHla3kESBsXfnfMhSmQ\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=645x825","https:\/\/pd1eu.bumbcdn.com\/p44\/hidden?euri=5e8kQofT84im5Y-dJ178LAaoB7OnAad146X4pUy3ehAwDFkNkpLy-nW00JohFvdzh6vCoTFBSvMh13jToHTueAuynF-kkbqaXxUT0WKvkd0bT3V1YHdVwoBGdYDEOqViw0OuFxZ9TpztYapIAvd3Jeeu4EWLJKQuZVWmz3jy.VekzKLxMER1cOQLL61w4E1pSJWmux1gINhbPwtjXlPmS7CkUpooVRji708PtFdcqZLCcLAboruRLA\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1323x973","https:\/\/pd1eu.bumbcdn.com\/p44\/hidden?euri=6.Ezy6b36SGr014sO3OPrn7XcAgimCWsjDLdQwOK-2tQtuq9BLl1jDRxmOkYgNQp8PUStHfZW-YWbC.5pllP6-zlad5HV4d1p-.M2Gqd6uq6Q6tCsstclbuSSFoPyCYPqBTGrJSQLHBZ8E7iDmxDjDcI8SstCreQfW7xqru5nIYMjT5O.Fvqwq8HcQmHC-FWKKifEW-iXJmybmciaAlGrZPdOFDVPEHPxAlL5q0GCLs7W3gLQJ4ZMg\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1235","https:\/\/pd1eu.bumbcdn.com\/p44\/hidden?euri=9749iumvTa4wv1XXk39AB3lYCEH84YzmdDVESyuOMBDvvZo4z77TtBCbvYs12b7Ae3onFO2JlIGWDRHp.8k.wU1BJ1tfm4WCKlNR6RPsJljYlvrtg8prE2hZh-9L4fJBFKaJ2TQ4LHomgmPodf2GpN36MO6c3MPBWsiXGcXp-1l8ofFuWyfJeOi6CMjjLigoT0DkZGGcFDwKBg885EOvIbyW2z85N8oGHVBnXS0.YQniUjF6jRmUIA\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1184x973","https:\/\/pd1eu.bumbcdn.com\/p44\/hidden?euri=N0NMyFjFn1SiUDrsJg4yEl0Xndvmc7kpgrGXTW9HLVzlVSbpggpEYzNwwg5SZf5ZWeR3BA75M5x7qbnfTPiXUQ2hrsd1UOxsQR-Yq2NSRBCG1Ur8KrDXiWrzN6JJrIbZzYYV8oukIBD54CKv-8R-Yyppf8kRhKgQUHi3-eeoa61sgvr870wDsDblSUvk3hidWNXLMve0Cz1FVwH52S.perWXfED.jGwEj6sVzB5Yq-LR0F1BUKRgKA\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=1318x973"],"distance":"","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Apolline","bio":"","age":23,"appId":"5de6bbf6d0baa40100e973be","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5de6bbf6d0baa40100e973be\/320x400_7034b9eb-2cc3-4250-b9ec-40945b1c244e.jpg","https:\/\/images-ssl.gotinder.com\/5de6bbf6d0baa40100e973be\/320x400_b6b64de7-b981-4e3f-a6e2-65daefffc0f1.jpg","https:\/\/images-ssl.gotinder.com\/5de6bbf6d0baa40100e973be\/320x400_22c83200-0a3a-4f92-ac68-3e584d39f8ea.jpg"],"distance":"14km","jobTitle":null,"school":null,"attributes":{"s_number":856359183}},{"app":"tiilt","fullName":"marane","bio":null,"age":26,"appId":"5429461","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6811\/79d20c97da6e42f43c10fac9dc199af0.jpg"],"distance":"Paris","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"CoolSimple","bio":null,"age":35,"appId":"5429489","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6811\/a37919027cec92f6a2caf1c767b2f638.jpg"],"distance":"Paris","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"Melanie","bio":null,"age":35,"appId":"5429486","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6811\/149b7aee61e644d2901f6e704232663a.jpg"],"distance":"Paris","jobTitle":null,"school":null,"attributes":[]},{"app":"bumble","fullName":"Morgane","bio":"Emploi : Coach chez Soccer Shots \n\r\u00c9tudes : SCAD, 2019 \n\rEmplacement : Longuenesse, Pas-de-Calais\n\u00e0 ~50 km \n\r\u00c0 propos de moi : \ud83c\uddfa\ud83c\uddf8\ud83c\uddeb\ud83c\uddf7 \n\rTaille : 170 cm (5\u0027 7\u0027\u0027) \n\rNiveau d\u0027\u00e9tudes : Niveau bac \n\rEst-ce que vous buvez\u00a0? : \u00c0 l\u0027occasion \n\rAvez-vous des animaux domestiques\u00a0? : Chiens \n\rQue recherchez-vous\u00a0? : Relation amoureuse \n\rVoulez-vous avoir des enfants\u00a0? : J\u0027aimerais en avoir \n\rQuel est votre signe astrologique\u00a0? : Vierge \n\rAvez-vous des id\u00e9es politiques\u00a0? : Gauche \n\rAvez-vous une religion\u00a0? : Ath\u00e9isme \n\rPlage ou montagne\u00a0? : Beach \n\rMon idole d\u0027enfance... : Bob Saget \n\rLa qualit\u00e9 que je pr\u00e9f\u00e8re chez quelqu\u0027un... : Sense of humor \n\r","age":25,"appId":"obff5b362880e659577a31f45ee4baff3d18c986a773a6aae","isFavorite":false,"pictures":["https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=tB1n4P4MGNbmpGIk0614aSt82ZCEXupJl4OlgI-69IKsf9q6NEFmFWgcn1Q21UuTDKTcSDSo76RBv1d9zuMqTmWZjlpeEz0ZKACbKO8O.6khJEc-87FjLQK11hvhRuQUmNc6mbH2hSVilR-wY.vbKOjMymbP3dQgJmuo6yQXmMwlfeJ.qaaTgZrioSPcyG3sUmmVoCqNcU8X39X6s1LUMb9StdhkHHQMT6eK6eOHn7XGOBKg2nqG35Ks6oTx3crf\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1392","https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=yH3vbUApQsUjij7e8rG0c6zXMgEs8Fdx1e8T29bXzuGQ9dsVxpBzyqYs3TPDmIvmsRZyN2CQjai5hZvY276F5c-ilNVbiCuMW.vgBKj2hGlvDKfk3dkfmN9UQc9DKl3jbesct0eDEd196A9GrQAEyACpeC8vwwNOhz3N8s1qYJfGrMg6p.AuiYwh5ZCZaogE2YD6mK803a0rejXm2m3aKP4.288G6S0JO9VQ0gQTFQAN.--MipZIvkxV-MhFZfvp\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1323","https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=kQYEnHWPCaC8uiznzvWzdcAqXxcA7cRyy5pZRvY-89nr5AsPdvA5jpc37oB-ynC-cJZiIC.-et3oSQqQBBx-aTCpi6IC7rkqXdpAK0unoEfeXeHcaXG9gc4wyrSA1i1HOESj29ZTiGocIbesjdRQvOKUJ7MoIWmqNy8PjZShbB-F1PJXrBWFcLAwZLq2PFghzdjR1qBBlTOEcL1bN-CNZ-GbUcqxNSPy6KUgEfQqwwAQRyJgomkC2VCvSYBzxQV4\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=643x840","https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=1iVvr5Pa988kbhkwayc4Gi2ZW9NvvNgj1LriW5cSSV05FGEcCO8CZDkeGkGAxYuQlHt2f-R1O8pq.7Iizn5B7iCFBZvKYygn8FkhY0j5Y1E-DsTbf8g.8CyoDW6mzw8.EFCw1VVTBonHTy7VRm-lAO7gdVTP5165PoQfOsXXzTTtcKBs4enK7YKzyry7lNQ27UhO.C9Ux1Q03Q4f7aSxFmk3384LT2PUrLHkwFRLHaEm7S8Mj7dw5g9RAKC7bV8A\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=673x1257","https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=495Lo1APy6qlge-lRJ.dHRXB8zJBQ7HzJMwbYTi0fJMQJe9iq08QdVuivnVrLtDTwU8xRwymTlovuEV6xxDCTDlsGbyBmFUgckrtvfCCprK6MV.FnHelmTX6FT3wcPuXKYlm8zUpYYKvrM8xQRSueqI9BnsvkS2r0n.9.thaxY99FpN1bYTlfgpom48Dc-OhmYtOLh1gp22yir0Lm2tbRCrCU0KY9hBsAYpspkwW-GRPfYc9B-UYNATXIU8AYnZk\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=973x1323","https:\/\/pd1eu.bumbcdn.com\/p93\/hidden?euri=ySuFVQHaE3Ca41mzu1YaoqRFo1hXXyfQh5AXVLmKBWInYg6Kj8HlK2KyOr15lnUIfA90YWelFuBD6nhOUOxjl-h3VkZLVSrT9PEeossa0wlGFjIMegQFUPHA2b6QDwSmZQpR0irpKxJlEKelz6GFYedYW9Ka8QncLygwu7V9pXJUT9r.T2uNLigh0z9W7WbJ5sg.trDr-E3UqYTuKhQ3Gxw1www1RzOliFY11Q3cJfYAr8mjNR.3lxc4.CIiIjUM\u0026size=__size__\u0026wm_size=72x72\u0026wm_offs=355x643"],"distance":"\u00e0 ~50 km","jobTitle":"Coach","school":"SCAD","attributes":[]},{"app":"tinder","fullName":"Laura","bio":"","age":24,"appId":"5ad3950078851f7942ef2081","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5ad3950078851f7942ef2081\/320x400_629f0700-b81b-4735-b6f9-e4a68493e690.jpg"],"distance":"7km","jobTitle":null,"school":null,"attributes":{"s_number":529378824}},{"app":"tiilt","fullName":"rose","bio":null,"age":61,"appId":"5429475","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/avatar3\/2.jpg"],"distance":"Marseille","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"Patt","bio":null,"age":45,"appId":"5429454","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/avatar3\/2.jpg"],"distance":"Montargis","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Cl\u00e9mence","bio":"","age":22,"appId":"537f96eed87784b71610c4a0","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_a5e59965-34fa-4bf8-88a2-e56615ae2081.jpg","https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_6527233f-7646-4b1a-bd67-2c1f3262fdd1.jpg","https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_5692720b-6716-4e3d-90b4-df2f042e6976.jpg","https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_9d067b62-3e6d-48b6-863a-e074ec127185.jpg","https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_973f6e27-85ee-44e7-a9d6-40e9b4643011.jpg","https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_9c5c1c52-439b-479e-8abc-f70f5c1f9183.jpg","https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_4455ac00-be41-4a12-add1-207431327600.jpg","https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_b074926d-7482-4c67-94e0-7e1901aa59b0.jpg","https:\/\/images-ssl.gotinder.com\/537f96eed87784b71610c4a0\/320x400_f1b3af6d-92f1-4e5a-938d-605dbb995b9b.jpg"],"distance":"14km","jobTitle":null,"school":"Universit\u00e9 Paris Descartes","attributes":{"s_number":45963632}},{"app":"tinder","fullName":"Chlo\u00e9","bio":"\ud83d\udccdCalais\/Lille\n\u26f7\ud83c\udfa7\ud83c\udfac\ud83d\udcf7\ud83d\udecd\ud83c\udf7b\ud83c\udf2f","age":20,"appId":"550ab730d390417d6764e337","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_9503f575-1ae8-4cf7-a298-69086635879c.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_9dc25489-0533-4046-80ce-ce4eca9730e0.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_579158ef-1647-42bb-8d41-a2876ef3fa9f.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_ac30f3c2-73cb-4d18-855c-1f3e441a4fe5.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x320_b53351b0-b9fe-44aa-8f05-90c0f7133ff7.jpg","https:\/\/images-ssl.gotinder.com\/550ab730d390417d6764e337\/320x400_3222089b-5b84-4646-bea8-1f053ea94b05.jpg"],"distance":"13km","jobTitle":null,"school":"Universit\u00e9 Lille 3","attributes":{"s_number":115525415}},{"app":"tinder","fullName":"Anais","bio":"insta: anaismqmqmb","age":21,"appId":"562b84ece30f1efe03bc3f1d","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/562b84ece30f1efe03bc3f1d\/320x400_9b921247-a49f-408d-b674-bf88df8b4554.jpg","https:\/\/images-ssl.gotinder.com\/562b84ece30f1efe03bc3f1d\/320x400_9f6b1a1f-fc66-4798-bf48-22eca4c71cea.jpg","https:\/\/images-ssl.gotinder.com\/562b84ece30f1efe03bc3f1d\/320x400_79d26437-29f6-4996-b079-3253d63e1b4f.jpg","https:\/\/images-ssl.gotinder.com\/562b84ece30f1efe03bc3f1d\/320x400_9c88034d-d286-49d7-b277-4206f4ab1223.jpg","https:\/\/images-ssl.gotinder.com\/562b84ece30f1efe03bc3f1d\/320x400_8617c574-a15b-4138-94ee-4ca8d956cf4a.jpg"],"distance":"1km","jobTitle":null,"school":"Lille 3","attributes":{"s_number":166108379}},{"app":"tiilt","fullName":"Nina","bio":null,"age":52,"appId":"5417961","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/avatar3\/2.jpg"],"distance":"Paris","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"nathou","bio":null,"age":39,"appId":"5425700","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6808\/4a2d843310e005b913792bab9c44ba1c.jpg"],"distance":"Saintes","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"cherry7","bio":null,"age":36,"appId":"4312818","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/5777\/71e06b04a94b3117e1c4ad2fe0b12897.jpg"],"distance":"Champigny-sur-marne","jobTitle":null,"school":null,"attributes":[]},{"app":"tiilt","fullName":"odile","bio":null,"age":68,"appId":"5369345","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/6763\/0d52712976402fe122812bc140d85787.jpg"],"distance":"Lyon","jobTitle":null,"school":null,"attributes":[]},{"app":"tinder","fullName":"Adrienne","bio":"From Champagne \ud83e\udd42\ud83c\uddeb\ud83c\uddf7 to LLN \ud83c\udde7\ud83c\uddea","age":20,"appId":"5cbc2fc3d540dc1500d57c27","isFavorite":false,"pictures":["https:\/\/images-ssl.gotinder.com\/5cbc2fc3d540dc1500d57c27\/320x400_60cb6b71-b7d2-445c-a9b2-3b45755add33.jpg","https:\/\/images-ssl.gotinder.com\/5cbc2fc3d540dc1500d57c27\/320x320_a34920e0-af9d-4528-83d6-894c8d8252e1.jpg","https:\/\/images-ssl.gotinder.com\/5cbc2fc3d540dc1500d57c27\/320x400_be2da84c-c675-4188-8686-8eecd6513b4a.jpg","https:\/\/images-ssl.gotinder.com\/5cbc2fc3d540dc1500d57c27\/320x400_91ab15ed-d3f6-45ba-86c9-371da18289ed.jpg"],"distance":"14km","jobTitle":null,"school":null,"attributes":{"s_number":732191076}},{"app":"tiilt","fullName":"Peggy","bio":null,"age":46,"appId":"5429481","isFavorite":false,"pictures":["https:\/\/photos.tiilt.fr\/l\/avatar3\/2.jpg"],"distance":"Douai","jobTitle":null,"school":null,"attributes":[]}]');

        $notConfigured = 0;

        $matches = array();
        if($app === 'all') {
            foreach ($this->services as $service) {
                if($service->isConfigured()) {
                    $matches = array_merge($matches,$service->getMatches());
                } else {
                    $notConfigured++;
                }
            }

            if(count($this->services) === $notConfigured) {
                throw new NotAcceptableHttpException("You need to configure at least one service for current user");
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
     * @param Request $request
     * @param string $app
     *
     * @Route("token/refresh", methods={"GET"})
     *
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
            foreach ($this->services as $service) {
                if($service->isConfigured() && method_exists($service,'getMessageList')) {
                    $discussions = array_merge($discussions,$service->getMessageList());
                } else {
                    $notConfigured++;
                }
            }

            if(count($this->services) === $notConfigured) {
                throw new NotAcceptableHttpException("You need to configure at least one service for current user");
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

        $apps = array(
            array(
                'app' => TinderService::APP,
                'title' => ucfirst(TinderService::APP),
                'img' => $request->getSchemeAndHttpHost() . '/assets/images/tinder_logo.jpg',
                'isConfigured' => $this->getService(TinderService::APP)->isConfigured()
            ),array(
                'app' => BumbleService::APP,
                'title' => ucfirst(BumbleService::APP),
                'img' => $request->getSchemeAndHttpHost() . '/assets/images/bumble_logo.png',
                'isConfigured' => $this->getService(BumbleService::APP)->isConfigured()
            ),array(
                'app' => TiiltService::APP,
                'title' => ucfirst(TiiltService::APP),
                'img' => $request->getSchemeAndHttpHost() . '/assets/images/tiilt_logo.jpg',
                'isConfigured' => $this->getService(TiiltService::APP)->isConfigured()
            )
        );


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