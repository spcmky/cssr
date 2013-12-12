<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StudentType extends AbstractType
{
    protected $options;

    public function __construct ( $options = array() ) {
        $this->options = $options;
    }

    public function buildForm ( FormBuilderInterface $builder, array $options )
    {
        $builder
            ->add('firstname')
            ->add('middlename')
            ->add('lastname')
            ->add('username')
            ->add('email','email')
            ->add('phone');

            $builder->add('plainPassword', 'repeated', array(
                'type' => 'password',
                'options' => array('translation_domain' => 'FOSUserBundle'),
                'first_options' => array('label' => 'form.new_password'),
                'second_options' => array('label' => 'form.new_password_confirmation'),
                'invalid_message' => 'fos_user.password.mismatch',
            ));

        $builder->add('entry','date',array(
            'widget' => 'text',
            'format' => 'MM / dd / yyyy',
            'input' => 'datetime',
            'data'  => $this->options['date'],
            'label' => 'Entry Date'
        ));

        $builder->add('dorm', 'entity', array(
            'class' => 'CssrMainBundle:Dorm',
            'choices' => $this->options['dorms'],
            'multiple'  => false,
            'expanded' => false
        ));


        $builder->add('enrollment','choice',array(
            'label' => 'Course Enrollment',
            'choices' => $this->getCourseChoices($this->options['centerCourses']),
            'mapped' => false,
            'multiple'  => true,
            'expanded' => true,
            'data' => $this->getCourseEnrollment($this->options['studentCourses']),
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\User'
        ));
    }

    private function getCourseChoices ( $courses ) {
        $options = array();
        foreach ( $courses as $course ) {
            $options[$course['id']] = $course['name'].' - '.$course['lastname'].', '.$course['firstname'];
        }
        return $options;
    }

    private function getCourseEnrollment ( $courses ) {
        $data = array();
        foreach ( $courses as $course ) {
            $data[] = $course['id'];
        }
        return $data;
    }

    public function getName()
    {
        return 'cssr_mainbundle_studenttype';
    }
}
