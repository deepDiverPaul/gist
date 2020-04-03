<?php

namespace Gist\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Knp\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class UserCreateCommand.
 *
 * @author Simon Vieille <simon@deblan.fr>
 */
class UserCreateCmdCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:cmdCreate')
            ->setDescription('Create a user without Questions')
            ->setHelp('Arguments: Username Password')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $userProvider = $this->getSilexApplication()['user.provider'];

        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        if ($userProvider->userExists($username)) {
            $output->writeln('<error>This username is already used.</error>');
        }else{
            $user = $userProvider->createUser();
            $user->setUsername($username);

            $userProvider->registerUser($user, $password);

            $output->writeln('<info>Created User '.$username.'</info>');
        }

    }
}
