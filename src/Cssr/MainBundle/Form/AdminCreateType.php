<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AdminCreateType extends AbstractType
{
    protected $options;

    public function __construct ( $options = array() ) {
        $this->options = $options;
    }

    public function buildForm ( FormBuilderInterface $builder, array $options ) {

        $builder->add('firstname','text',array(
            'required' => true
        ));

        //$builder->add('middlename');

        $builder->add('lastname','text',array(
            'required' => true
        ));

        $builder->add('email','hidden',array(
            'data' => time().'@fake.com'
        ));

        //$builder->add('phone');

        $builder->add('username','text');

        $builder->add('plainPassword', 'repeated', array(
            'type' => 'password',
            'options' => array('translation_domain' => 'FOSUserBundle'),
            'first_options' => array('label' => 'Password','attr'=> array('autocomplete'=>'off')),
            'second_options' => array('label' => 'Re-enter Password','attr'=> array('autocomplete'=>'off')),
            'invalid_message' => 'fos_user.password.mismatch'
        ));

        $builder->add('group','hidden',array(
            'data' => $this->getGroup(),
            'mapped' => false
        ));
    }

    public function setDefaultOptions ( OptionsResolverInterface $resolver ) {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\User',
            'validation_groups' => array('Registration')
        ));
    }

    private function getGroup() {
        return $this->options['group']->getId();
    }

    public function getName() {
        return 'cssr_mainbundle_admin_create_type';
    }
}
