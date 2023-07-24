<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use function Symfony\Bundle\FrameworkBundle\Controller\redirectToRoute;
use function Symfony\Component\DomCrawler\image;

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
        if ($this->checkAdmin()) {
            //get Product
            $em = $doctrine->getManager();
            $product = $em->getRepository('App\Entity\Product')->find($id);

            if (!is_null($product)) {
                //remove image from server if exists
                if (!is_null($product->getImage())) {
                    $filesystem = new Filesystem();
                    $currentImg = $this->getParameter('img_dir') . $product->getImage();
                    $filesystem->remove($currentImg);
                    $this->addFlash(
                        'error',
                        'Image ' . $product->getImage() . ' removed!'
                    );
                }

                //remove Product info from database
                $em->remove($product);
                $em->flush();

                $this->addFlash(
                    'error',
                    'Product deleted!'
                );
            } else {
                $this->addFlash(
                    'error',
                    'Product not existed!'
                );
            }

            return $this->redirectToRoute('app_products');
        } else {
            return $this->redirectToRoute('app_products');
        }
    }

    #[Route('/product/new/', name: 'app_new_product')]
    public function createAction(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        if ($this->checkAdmin()) {
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
        } else {
            return $this->redirectToRoute('app_products');
        }
    }

    #[Route('/product/update/{id}', name: 'app_update_product')]
    public function updateAction(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger, $id): Response
    {
        if ($this->checkAdmin()) {
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
                        //remove existing image
                        if (!is_null($product->getImage())) {
                            $filesystem = new Filesystem();
                            $currentImg = $this->getParameter('img_dir') . $product->getImage();
                            $filesystem->remove($currentImg);
                        }
                        //upload new image to server
                        $uploadImg->move(
                            $destination,
                            $newImgName
                        );
                    } catch (FileException $e) {
                        $this->addFlash(
                            'error',
                            'Cannot upload image'
                        );
                    }
                    //set image name in database
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
        } else {
            return $this->redirectToRoute('app_products');
        }
    }

    private function checkAdmin() {
        //redirect the user if the user is not admin
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            $this->addFlash(
                'error',
                'Access denied!'
            );
            return false;
        } else {
            return true;
        }
    }
}
