<?php

// src/Form/AmbtenaarType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AmbtenaarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('aanhef', ChoiceType::class, [
            'attr'       => ['class' => 'form-control'],
            'label'      => 'Aanhef',
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => false,
            'empty_data' => 'Dhr.',
            'choices'    => [
                'Dhr.' => 'Dhr.',
                'Mvr.' => 'Mvr.',
            ],

        ])
        ->add('voornaamen', TextType::class, [
            'attr'       => ['class' => 'form-control'],
            'label'      => 'Voornaam',
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => false,
            'empty_data' => 'John',

        ])
        ->add('geslachtsnaam', TextType::class, [
            'attr'       => ['class' => 'form-control'],
            'label'      => 'Achternaam',
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => false,
            'empty_data' => 'Doe',

        ])
        ->add('samenvatting', TextareaType::class, [
            'attr'       => ['class' => 'form-control'],
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => false,
            'empty_data' => 'Een top ambtenaar',

        ])
        ->add('beschrijving', TextareaType::class, [
            'attr'       => ['class' => 'form-control'],
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => false,
            'empty_data' => 'Maar dan ook echt een top ambtenaar!',

        ])
        ->add('opslaan', SubmitType::class, [
            'attr' => ['class' => 'btn btn-primary'],
        ]);
    }
}
