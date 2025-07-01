<?php

namespace App\EventListener;

use App\Entity\AccessToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JWTLoginSuccessListener
{
public function __construct(private EntityManagerInterface $em) {}

public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
{
$token = $event->getData()['token'];
$user = $event->getUser();

$accessToken = new AccessToken();
$accessToken->setToken($token);
    if ($user instanceof User) {
        $accessToken->setUserToken($user);
    }

    $accessToken->setExpiresAt((new \DateTimeImmutable())->add(new \DateInterval('PT1H'))); // token de 1 hora

$this->em->persist($accessToken);
$this->em->flush();
}
}
