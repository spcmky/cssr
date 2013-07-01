<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ScoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('value')
            ->add('comment')
            ->add('created')
            ->add('modified')
            ->add('course')
            ->add('student')
            ->add('standards')
            ->add('modifier')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\Score'
        ));
    }

    public function getName()
    {
        return 'cssr_mainbundle_scoretype';
    }
}
