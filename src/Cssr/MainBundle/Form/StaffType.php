<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StaffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('password')
            ->add('firstname')
            ->add('middlename')
            ->add('lastname')
            ->add('email')
            ->add('phone');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\User'
        ));
    }

    public function getName()
    {
        return 'cssr_mainbundle_stafftype';
    }
}
