<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

#[Route('/api')]
class AuthApiController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        AuthenticationManagerInterface $authenticationManager,
        JWTTokenManagerInterface $JWTManager,
        UserProviderInterface $userProvider
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username'], $data['password'])) {
            return new JsonResponse(['error' => 'Identifiants incomplets'], 400);
        }

        try {
            $user = $userProvider->loadUserByIdentifier($data['username']);

            $token = new UsernamePasswordToken($user, $data['password'], 'main', $user->getRoles());
            $authenticationManager->authenticate($token);

            $jwt = $JWTManager->create($user);

            return new JsonResponse(['token' => $jwt]);
        } catch (AuthenticationException $e) {
            return new JsonResponse(['error' => 'Identifiants invalides'], 401);
        }
    }
}
