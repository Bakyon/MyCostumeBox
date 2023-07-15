<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
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

        if ($form->isSubmitted() && $form->isValid()) {
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
}
