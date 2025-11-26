<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTSuccessHandler
{
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        $data = $event->getData();

        // Add user info to the response
        $data['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ];

        $event->setData($data);
    }
}
