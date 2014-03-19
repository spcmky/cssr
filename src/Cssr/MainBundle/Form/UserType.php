<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class UserType extends AbstractType {

    protected $options;

    public function __construct ( $options = array() ) {
        $this->options = $options;
    }

    public function buildForm ( FormBuilderInterface $builder, array $options ) {

        $builder->add('firstname');

        //$builder->add('middlename');

        $builder->add('lastname');

        //$builder->add('email','email');

        //$builder->add('phone');

        $builder->add('username');

        $builder->add('plainPassword', 'repeated', array(
            'type' => 'password',
            'options' => array('translation_domain' => 'FOSUserBundle'),
            'first_options' => array('label' => 'form.new_password'),
            'second_options' => array('label' => 'Re-enter Password'),
            'invalid_message' => 'fos_user.password.mismatch',
            'required' => false
        ));
    }

    public function setDefaultOptions ( OptionsResolverInterface $resolver ) {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\User'
        ));
    }

    public function getName() {
        return 'cssr_mainbundle_usertype';
    }
}
