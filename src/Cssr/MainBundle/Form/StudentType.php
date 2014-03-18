<?php

namespace Cssr\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Cssr\MainBundle\Form\Type\FieldsetType;


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
            ->add('email','email')
            ->add('phone');

        $builder->add('username');
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

        $builder->add('courses',new FieldsetType(),array(
            'label' => false,
            'required' => false,
            'mapped' => false,
            'title' => 'Courses',
            'subforms' => $this->addCourseForms()
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cssr\MainBundle\Entity\User',
            'validation_groups' =>  array('Registration', 'Default')
        ));
    }

    private function addCourseForms() {
        $centerCourses = $this->options['centerCourses'];
        $studentCourses = $this->options['studentCourses'];

        $courses = array();
        $courseStaff = array();
        $courseStudent = array();
        foreach ( $centerCourses as $course ) {
            if ( !isset($courses[$course['name']]) ) {
                $courses[$course['name']] = array();
                $courseStaff[$course['name']] = array();

                $courseStudent[$course['name']] = null;
                foreach ( $studentCourses as $sc ) {
                    if ( $course['id'] === $sc['id'] ) {
                        $courseStudent[$course['name']] = $course['id'];
                    }
                }
            }
            $courses[$course['name']] = $course['name'];
            $courseStaff[$course['name']][$course['id']] = $course['lastname'].', '.$course['firstname'];


        }

        $forms = array();
        foreach ( $courses as $c ) {
            $forms[] = array(
                'name' => $c,
                'type' => 'choice',
                'attr' => array(
                    'label_attr' => array('class' => 'studentEditCourseLabel'),
                    'label' => $c,
                    'choices' => $courseStaff[$c],
                    'mapped' => false,
                    'multiple'  => false,
                    'expanded' => false,
                    'data' => $courseStudent[$c],
                    'empty_value' => 'Select Staff',
                    'empty_data'  => null,
                    'required' => false
                )
            );
        }

        return $forms;
    }

    public function getName() {
        return 'cssr_mainbundle_studenttype';
    }
}
