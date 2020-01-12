<?php

namespace App\Command;

use App\Entity\Match;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\TinderService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshTokenCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:refresh-token';


    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var TinderService
     */
    protected $tinderService;


    /**
     * SuperLikeCommand constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em,TinderService $tinderService)
    {

        $this->em = $em;
        $this->tinderService = $tinderService;

        parent::__construct();
    }


    protected function configure()
    {

        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Super Like all profiles that can be liked')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command is run by a cron job in order to super like profiles that can be');

    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {


        /** @var UserRepository $repo */
        $repo = $this->em->getRepository(User::class);

        /** @var User[] $users */
        $users = $repo->findAll();

        foreach ($users as $user) {

            if($user->getTinderRefreshToken()) {
                $this->tinderService->setUser($user);
                $this->tinderService->refreshToken();
            }
        }

        $this->em->flush();


        return 0;
    }
}
