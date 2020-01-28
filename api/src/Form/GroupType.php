<?php

// src/Form/AmbtenaarType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('name', TextType::class, [
            'attr'       => ['class' => 'form-control'],
            'label'      => 'Naam',
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => true,
            'empty_data' => 'Mijn product groep',

        ])
        ->add('description', TextareaType::class, [
            'attr'       => ['class' => 'form-control'],
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => false,
            'empty_data' => 'Dit is natuurlijk de beste product groep ooit',

        ])
        ->add('save', SubmitType::class, [
            'attr' => ['class' => 'btn btn-primary'],
        ]);
    }
}
