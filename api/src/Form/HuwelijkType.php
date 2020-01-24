<?php

// src/Form/AmbtenaarType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;

class HuwelijkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('type', ChoiceType::class, [
            'attr'       => ['class' => 'form-control'],
            'label'      => 'Type',
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => true,
            'choices'    => [
                'Huwelijk'    => 'huwelijk.',
                'Parterschap' => 'partnerschap',
            ],

        ])
        ->add('primairProduct', ChoiceType::class, [
            'attr'       => ['class' => 'form-control'],
            'label'      => 'Ceremonie',
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => true,
            'choices'    => [],

        ])
        /*
        ->add('datum', DateType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Datum',
                'label_attr' => ['class' => 'control-label col-sm-2'],
                'required'   => false

        ])
        ->add('tijd', TimeType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Tijdstip',
                'label_attr' => ['class' => 'control-label col-sm-2'],
                'required'   => false

        ])
        */
        ->add('trouwAmbtenaar', ChoiceType::class, [
            'attr'       => ['class' => 'form-control'],
            'label'      => 'Ambtenaar',
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => true,
            'choices'    => [],

        ])
        ->add('locatie', ChoiceType::class, [
            'attr'       => ['class' => 'form-control'],
            'label'      => 'Locatie',
            'label_attr' => ['class' => 'control-label col-sm-2'],
            'required'   => true,
            'choices'    => [],

        ])
        ->add('opslaan', SubmitType::class, [
            'attr' => ['class' => 'btn btn-primary'],
        ]);
    }
}
