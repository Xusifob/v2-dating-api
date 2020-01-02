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

class SuperLikeCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:super-like';


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

        $profileRepo = $this->em->getRepository(Profile::class);

        /** @var User[] $users */
        $users = $repo->findSuperLiker();

        foreach ($users as $user) {

            $settings = $user->getSettings();

            // If user is not in settings, do nothing
            if(!isset($settings['autoSuperLike']) || !$settings['autoSuperLike']) {
                continue;
            }


            /** @var Profile $profile */
            $profile = $profileRepo->findOneBy(array(
                'owner' => $user
            ));

            // If no profile is found, do nothing
            if(!$profile) {
                continue;
            }

            $this->tinderService->setUser($user);
            $this->tinderService->refreshToken();

            /** @var Match $match */
            $match = $this->tinderService->superLike($profile,true);
            if($match->getNextAction()) {
                $user->setNextSuperLike($match->getNextAction());
                $output->writeln("Super like tried for {$user->getFullName()} on {$profile->getFullName()}");
            } else {
                // The like has worked
                $dateTime = new \DateTime();
                $dateTime->modify('+1 day');


                $output->writeln("Super like done for {$user->getFullName()} on {$profile->getFullName()}");

                // Delete the profile that has been liked
                $this->em->remove($profile);

                $user->setNextSuperLike($dateTime);
            }
            $this->em->persist($user);
        }

        $this->em->flush();


        return 0;
    }
}
