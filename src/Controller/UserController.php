<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Bundle\FrameworkBundle\Controller\redirectToRoute;

class UserController extends AbstractController
{
    #[Route('/register', name: 'app_user_register')]
    public function registerAction(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_products');
        }
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            if ($this->userExisted($doctrine, $username)) {
                $this->addFlash(
                    'notice',
                    'Username already exited! Please choose another one!'
                );
                return $this->redirectToRoute('app_user_register');
            } else {
                $user->setRoles(["ROLE_USER"]);
                $plainTextPW = $form->get('password')->getData();
                $hashedPW = $passwordHasher->hashPassword($user, $plainTextPW);
                $user->setPassword($hashedPW);

                $em = $doctrine->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash(
                    'notice',
                    'Register successfully!'
                );
                return $this->redirectToRoute('app_products');
            }
        }
        return $this->renderForm('user/register.html.twig', ['form' => $form]);
    }

    private function userExisted(ManagerRegistry $doctrine, $username) {
        $check = $doctrine->getRepository('App\Entity\User')->findBy(array('username' => $username));
        if (is_null($check)) {
            return false;
        } else {
            return true;
        }
    }

    #[Route('admin/dashboard', name: 'app_dashboard')]
    public function dashboard(ManagerRegistry $doctrine): Response {
        $accounts = $doctrine->getRepository('App\Entity\User')->findAllNotAdmin();

        $accType = "";
        return $this->render('security/dashboard.html.twig', [
            'accType' => $accType,
            'accounts' => $accounts
        ]);
    }

    #[Route('/admin/dashboard/{accType}', name: 'app_accounts')]
    public function accountDashboard(ManagerRegistry $doctrine, $accType): Response {
        $accType = "%".$accType."%";
        $accounts = $doctrine->getRepository('App\Entity\User')->findByRole($accType);
        return $this->render('security/dashboard.html.twig', [
            'accType' => $accType,
            'accounts' => $accounts
        ]);
    }
}
