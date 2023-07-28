<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('app_profile');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/profile', name: 'app_profile')]
    public function profilePage(): Response {
        $user = $this->getUser();
        if ($user->getUserInfo()) {
            return $this->render('security/profile.html.twig', [
                    'user' => $user
                ]
            );
        } else {
            return $this->redirectToRoute('app_accinfo_update');
        }
    }

    #[Route(path: '/admin/dashboard/promote/{id}', name: 'app_promote')]
    public function promoteAction(ManagerRegistry $doctrine, $id): Response
    {
        $user = $doctrine->getRepository('App\Entity\User')->find($id);
        $user->setRoles(["ROLE_MOD"]);
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        $this->addFlash(
            'notice',
            'Promote successfully!'
        );
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route(path: '/admin/dashboard/demote/{id}', name: 'app_demote')]
    public function demoteAction(ManagerRegistry $doctrine, $id): Response
    {
        $user = $doctrine->getRepository('App\Entity\User')->find($id);
        $user->setRoles(["ROLE_USER"]);
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        $this->addFlash(
            'notice',
            'Demote successfully!'
        );
        return $this->redirectToRoute('app_dashboard');
    }
}
