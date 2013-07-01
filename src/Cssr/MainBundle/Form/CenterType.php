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
            ->add('description')
            ->add('address')
            ->add('city')
            ->add('state')
            ->add('postcode')
            ->add('phone')
            ->add('dorms')
            ->add('vocations');

        //$builder->add('dorms', 'collection', array('type' => new DormType(),'allow_add' => true));
        //$builder->add('vocations', 'collection', array('type' => new VocationType(),'allow_add' => true));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\Center'
        ));
    }

    public function getName()
    {
        return 'cssr_mainbundle_centertype';
    }
}
