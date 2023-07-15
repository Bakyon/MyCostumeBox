<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class)
            ->add('owner', TextType::class)
            ->add('image', TextType::class)
            ->add('size', choiceType::class, [
                'choices' => [
                    'S' => 0,
                    'SM' => 1,
                    'M' => 2,
                    'ML' => 3,
                    'L' => 4,
                    'XL' => 5
                ]
            ])
            ->add('price', NumberType::class)
            ->add('status', choiceType::class, [
                'choices' => [
                    'Cho thuê' => 0,
                    'Rao bán' => 1
                ]
            ])
            ->add('priceFes', NumberType::class)
            ->add('category', EntityType::class, array('class'=>'App\Entity\Category', 'choice_label'=>'name'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
