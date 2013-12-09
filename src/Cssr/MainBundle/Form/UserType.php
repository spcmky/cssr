<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class UserType extends AbstractType
{
    protected $em;

    public function __construct ( $em = null ) {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname')
            ->add('middlename')
            ->add('lastname')
            ->add('username')
            ->add('email','email');

        $builder->add('phone');

        $builder->add('plainPassword', 'repeated', array(
            'type' => 'password',
            'options' => array('translation_domain' => 'FOSUserBundle'),
            'first_options' => array('label' => 'form.new_password'),
            'second_options' => array('label' => 'form.new_password_confirmation'),
            'invalid_message' => 'fos_user.password.mismatch',
        ));

        $builder->add('center', 'entity', array(
            'class' => 'CssrMainBundle:Center',
            'choices' => $this->getCenterChoices(),
            'multiple'  => false,
            'expanded' => false,
            'required' =>false
        ));

        $builder->add('groups', 'entity', array(
            'label' => 'Title',
            'class' => 'CssrMainBundle:Group',
            'choices' => $this->getGroupChoices(),
            'multiple'  => true
        ));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\User'
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
        return 'cssr_mainbundle_usertype';
    }
}
