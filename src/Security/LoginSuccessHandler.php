<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private const ROUTE_MAP = [
        'ROLE_RH' => 'app_rh_dashboard',
        'ROLE_OFFICIER' => 'app_officier_dashboard',
        'ROLE_MILITAIRE' => 'app_militaire_dashboard'
    ];

    public function __construct(
        private readonly RouterInterface $router,
        private readonly Security $security
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        
        if ($user === null) {
            throw new AccessDeniedException('User not authenticated');
        }

        foreach (self::ROUTE_MAP as $role => $route) {
            if ($this->security->isGranted($role)) {
                return new RedirectResponse($this->router->generate($route));
            }
        }

        // Fallback pour les utilisateurs sans rôle reconnu
        return new RedirectResponse($this->router->generate('app_login'));
    }
}