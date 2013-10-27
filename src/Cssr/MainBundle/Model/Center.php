<?php

namespace Cssr\MainBundle\Model;

class Center {

    public static function getCourses ( $em, $center ) {
        $sql = "
        SELECT C.id, C.area_id, C.user_id
        FROM cssr_course C
        LEFT JOIN cssr_area A ON A.id = C.area_id
        LEFT JOIN cssr_user U ON U.id = C.user_id
        WHERE U.center_id = :centerId";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('centerId', $center->getId());
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