<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserInfo;
use App\Form\UserInfoType;
use App\Form\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
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
                    'Username '.$username.' already exited! Please choose another one!'
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
        $accounts = $doctrine->getRepository('App\Entity\User')->findAll();
        $check = false;
        foreach ($accounts as $acc) {
            if ($acc->getUsername() == $username) $check = true;
        }
        return $check;
    }

    #[Route('/admin', name: 'app_dashboard')]
    public function dashboard(ManagerRegistry $doctrine): Response {
        $role = $this->getUser()->getRoles();
        if (!in_array("ROLE_ADMIN", $role)) {
            return $this->redirectToRoute('app_accounts', array('accType' => 'user'));
        }
        $accounts = $doctrine->getRepository('App\Entity\User')->findAllNotAdmin();

        $accType = "";
        return $this->render('security/dashboard.html.twig', [
            'accType' => $accType,
            'accounts' => $accounts
        ]);
    }

    #[Route('/admin/{accType}', name: 'app_accounts')]
    public function dashboardByAccType(ManagerRegistry $doctrine, $accType): Response {
        $accType = "%".$accType."%";
        $accounts = $doctrine->getRepository('App\Entity\User')->findByRole($accType);
        return $this->render('security/dashboard.html.twig', [
            'accType' => $accType,
            'accounts' => $accounts
        ]);
    }

    #[Route('/admin/delete/{id}', name: 'app_acc_delete')]
    public function deleteAcc(ManagerRegistry $doctrine, $id): Response
    {
        //get Account
        $em = $doctrine->getManager();
        $acc = $em->getRepository('App\Entity\User')->find($id);

        if (!is_null($acc)) {
            //remove Account info from database
            $em->remove($acc);
            $em->flush();

            $this->addFlash(
                'error',
                'Account deleted!'
            );
        } else {
            $this->addFlash(
                'error',
                'Account not existed!'
            );
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/profile/changepw', name: 'app_pw_change')]
    public function changePWAction(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        //get Account
        $acc = $this->getUser();
        $form = $this->createForm(UserType::class, $acc);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainTextPW = $form->get('password')->getData();
            $hashedPW = $passwordHasher->hashPassword($acc, $plainTextPW);
            $acc->setPassword($hashedPW);

            $em = $doctrine->getManager();
            $em->persist($acc);
            $em->flush();

            $this->addFlash(
                'notice',
                'Password is successfully changed successfully!'
            );
            return $this->redirectToRoute('app_profile');
        }
        return $this->renderForm('user/changepw.html.twig', ['form' => $form, 'user' => $acc]);
    }

    #[Route('/admin/resetpw/{id}', name: 'app_pw_reset')]
    public function resetpwAction(ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher, $id): Response
    {
        $user = $doctrine->getRepository('App\Entity\User')->find($id);
        $plainTextPW = "000000";
        $hashedPW = $passwordHasher->hashPassword($user, $plainTextPW);
        $user->setPassword($hashedPW);

        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        $this->addFlash(
            'notice',
            'Password is reset successfully successfully!'
        );
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/profile/updateinfo', name: 'app_accinfo_update')]
    public function updateInfoAccount(ManagerRegistry $doctrine, Request $request): Response
    {
        $user = $this->getUser();
        $info = $user->getUserInfo();
        if (!$info)
            $info = new UserInfo();
        $form = $this->createForm(UserInfoType::class, $info);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $info->setUser($user);
            $em = $doctrine->getManager();
            $em->persist($info);
            $em->flush();

            $this->addFlash(
                'notice',
                'Update successfully!'
            );
            return $this->redirectToRoute('app_profile');
        }
        return $this->renderForm('user/updateInfo.html.twig', [
            'form' => $form,
            'info' => $info
        ]);
    }
}
