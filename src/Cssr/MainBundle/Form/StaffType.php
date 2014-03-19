<?php

namespace Cssr\MainBundle\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StaffType extends AbstractType {

    public function __construct ( $options = array() ) {
        $this->options = $options;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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

        $builder->add('area','choice',array(
            'label' => 'Area',
            'choices' => $this->getCourses($this->options['centerCourses']),
            'mapped' => false,
            'multiple'  => false,
            'expanded' => false,
            'data' => $this->getStaffCourses($this->options['staffCourses'])
        ));
    }

    private function getCourses ( $courses ) {
        $data = array();
        foreach ( $courses as $course ) {
            $data[$course->getId()] = $course->getName();
        }
        return $data;
    }

    private function getStaffCourses ( $courses ) {
        $data = null;
        foreach ( $courses as $course ) {
            return $course['area_id'];
        }
        return $data;
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
