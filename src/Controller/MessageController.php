<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\ChatMember;
use App\Entity\Message;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chats', name: 'message_')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    #[Route('/{id}/messages', name: 'list_messages', methods: ['GET'])]
    public function getMessages(int $id): JsonResponse
    {
        [$chat] = $this->getValidatedChatForUser($id);

        $messages = $chat->getMessages();
        $data = [];

        foreach ($messages as $message) {
            $data[] = [
                'id' => $message->getId(),
                'sender' => $message->getSender()->getId(),
                'content' => $message->getContent(),
                'sent_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json(['messages' => $data]);
    }

    #[Route('/{id}/messages', name: 'send_message', methods: ['POST'])]
    public function sendMessage(int $id, Request $request): JsonResponse
    {
        [$chat, $user] = $this->getValidatedChatForUser($id);

        $data = json_decode($request->getContent(), true);
        if (empty($data['message'])) {
            return $this->json(['error' => 'El mensaje no puede estar vacío'], 400);
        }

        $message = new Message();
        $message->setChat($chat);
        $message->setSender($user);
        $message->setContent($data['message']);
        $message->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($message);
        $this->em->flush();

        return $this->json(['message' => 'Mensaje enviado']);
    }


    /**
     * Valida que el chat exista y que el usuario esté autorizado
     */
    private function getValidatedChatForUser(int $id): array
    {
        $user = $this->security->getUser();

        $chat = $this->em->getRepository(Chat::class)->find($id);

        if (!$chat) {
            throw $this->createNotFoundException('Chat no encontrado');
        }

        $isMember = $this->em->getRepository(ChatMember::class)
            ->findOneBy(['chat' => $chat, 'user_associated' => $user]);

        if (!$isMember) {
            throw $this->createAccessDeniedException('Acceso denegado');
        }

        return [$chat, $user];
    }
}
