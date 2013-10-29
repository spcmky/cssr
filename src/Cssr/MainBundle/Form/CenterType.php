<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CenterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('address')
            ->add('city')
            ->add('state')
            ->add('postcode')
            ->add('phone');

        $builder->add('dorms', 'collection', array(
            'type' => new DormType(),
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'required' => false
        ));

        $builder->add('vocations', 'collection', array(
            'type' => new VocationType(),
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'required' => false
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\Center',
            'cascade_validation' => true
        ));
    }

    public function getName()
    {
        return 'cssr_mainbundle_centertype';
    }
}
