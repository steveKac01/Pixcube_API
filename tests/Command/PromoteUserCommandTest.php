<?php

namespace App\Tests\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PromoteUserCommandTest extends KernelTestCase
{

    private function tester(): CommandTester
    {
        return new CommandTester((new Application(self::$kernel))->find('app:user:promote'));
    }

    public function testPromoteGrantsAdminRole(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = (new User())->setEmail('promote@test.fr')->setPassword('not-a-real-hash');
        $em->persist($user);
        $em->flush();

        $tester = $this->tester();
        $tester->execute(['email' => 'promote@test.fr']);

        $tester->assertCommandIsSuccessful();
        $em->refresh($user);
        $this->assertContains(User::ROLE_ADMIN, $user->getRoles());
    }

    public function TestPromoteFailsForUnknownUse(): void
    {
        self::bootKernel();

        $tester = $this->tester();
        $tester->execute(['email' => 'notexistinguser@test.fr']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }
}
