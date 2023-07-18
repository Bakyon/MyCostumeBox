<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    #[Route('/', name: 'app_products')]
    public function index(ManagerRegistry $doctrine): Response
    {
        //$products = $doctrine->getRepository('App\Entity\Product')->findAll();
        $products = $doctrine->getRepository('App\Entity\Product')->findAll();
        return $this->render('product/index.html.twig', [
            'controller_name' => 'ProductController',
            'products' => $products
        ]);
    }

    #[Route('/product/detail/{id}', name: 'app_product_detail')]
    public function detailsAction(ManagerRegistry $doctrine, $id): Response
    {
        
        //$product = $doctrine->getRepository('App\Entity\Product')->find($id);
        $product = $doctrine->getRepository('App\Entity\Product')->find($id);

        return $this->render('product/details.html.twig',
            ['product' => $product]
        );
    }

    #[Route('/product/delete/{id}', name: 'app_product_delete')]
    public function deleteAction(ManagerRegistry $doctrine, $id): Response
    {
        $em =$doctrine->getManager();
        $product = $em->getRepository('App\Entity\Product')->find($id);
        $em->remove($product);
        $em->flush();

        $this->addFlash(
            'error',
            'Product deleted!'
        );

        return $this->redirectToRoute('app_products');
    }

    #[Route('/product/new/', name: 'app_new_product')]
    public function createAction(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        $uploadImg = $form['image']->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            //Handle uploaded image if needed
            if ($uploadImg) {
                $destination = $this->getParameter('kernel.project_dir').'/public/Images';
                $originalImgName = pathinfo($uploadImg->getClientOriginalName(), PATHINFO_FILENAME);
                $newImgName = $slugger->slug($originalImgName).'-'.uniqid().'.'.$uploadImg->guessExtension();

                try {
                    $uploadImg->move(
                        $destination,
                        $newImgName
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );
                }
                $product->setImage($newImgName);
            }
            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash(
                'notice',
                'New product added!'
            );
            return $this->redirectToRoute('app_products');
        }
        return $this->renderForm('product/create.html.twig', ['form' => $form]);
    }

    #[Route('/product/update/{id}', name: 'app_update_product')]
    public function updateAction(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger, $id): Response
    {
        $product = $doctrine->getRepository('App\Entity\Product')->find($id);
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        $uploadImg = $form['image']->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            //Handle uploaded image if needed
            if ($uploadImg) {
                $destination = $this->getParameter('kernel.project_dir').'/public/Images';
                $originalImgName = pathinfo($uploadImg->getClientOriginalName(), PATHINFO_FILENAME);
                $newImgName = $slugger->slug($originalImgName).'-'.uniqid().'.'.$uploadImg->guessExtension();

                try {
                    $uploadImg->move(
                        $destination,
                        $newImgName
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );
                }
                $product->setImage($newImgName);
            }
            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash(
                'notice',
                'Product updated!'
            );
            return $this->redirectToRoute('app_products');
        }
        return $this->renderForm(
            'product/update.html.twig', [
                'form' => $form,
                'product' => $product
            ]
        );
    }
}
