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
            'invalid_message' => 'fos_user.password.mismatch'
        ));

        $builder->add('center', 'entity', array(
            'class' => 'CssrMainBundle:Center',
            'choices' => array($this->options['center']),
            'multiple'  => false,
            'expanded' => false,
            'required' => true
        ));

        $builder->add('groups', 'entity', array(
            'label' => 'Title',
            'class' => 'CssrMainBundle:Group',
            'choices' => $this->options['groups'],
            'multiple'  => true,
            'data' => new ArrayCollection(array($this->options['group']))
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
