<?php

// src/EventListener/JWTSuccessHandler.php
namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JWTSuccessHandler
{
public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
{
$user = $event->getUser();
$data = $event->getData();

// Agrega solo el ID del usuario
$data['user_id'] = $user->getId();
$event->setData($data);
}
}
