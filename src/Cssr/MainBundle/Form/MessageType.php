<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MessageType extends AbstractType
{
    protected $em;

    public function __construct ( $em = null ) {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title','text',array(
            'required' => true
        ));

        $builder->add('body','textarea',array(
            'attr' => array(
                'rows' => 5
            )
        ));

        $builder->add('center', 'entity', array(
            'class' => 'CssrMainBundle:Center',
            'choices' => $this->getCenterChoices(),
            'multiple'  => false,
            'expanded' => false
        ));

        $builder->add('groups', 'entity', array(
            'class' => 'CssrMainBundle:Group',
            'choices' => $this->getGroupChoices(),
            'multiple'  => true,
            'expanded' => true
        ));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\Message',
            'cascade_validation' => true
        ));
    }

    private function getGroupChoices() {
        $groups = $this->em->getRepository('CssrMainBundle:Group')->findAll();
        return $groups;
    }

    private function getCenterChoices() {
        $centers = $this->em->getRepository('CssrMainBundle:Center')->findAll();
        return $centers;
    }

    public function getName()
    {
        return 'cssr_mainbundle_messagetype';
    }
}
