<?php

namespace Cssr\MainBundle\Model;


class Student {

    public static function getCourses ( $em, $student ) {

        $sql = "
        SELECT C.id, C.area_id, C.user_id
        FROM cssr_student_course UC
        LEFT JOIN cssr_course C ON C.id = UC.course_id
        LEFT JOIN cssr_area A ON A.id = C.area_id
        LEFT JOIN cssr_user U ON U.id = C.user_id
        WHERE UC.student_id = :userId";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $student->getId());
        $stmt->execute();
        $items = $stmt->fetchAll();

        $courses = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ( $items as $c ) {
            $course = $em->getRepository('CssrMainBundle:Course')->find($c['id']);
            $area = $em->getRepository('CssrMainBundle:Area')->find($c['area_id']);
            $user = $em->getRepository('CssrMainBundle:User')->find($c['user_id']);

            $course->setArea($area);
            $course->setUser($user);

            $courses->add($course);
        }

        return $courses;
    }
}