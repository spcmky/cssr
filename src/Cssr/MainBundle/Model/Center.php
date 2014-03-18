<?php

namespace Cssr\MainBundle\Model;

class Center {

    public static function getCourses ( $em, $center ) {
        $sql = "
        SELECT C.id, C.area_id, C.user_id, A.name, U.firstname, U.lastname
        FROM cssr_course C
        INNER JOIN cssr_area A ON A.id = C.area_id
        LEFT JOIN cssr_user U ON U.id = C.user_id
        WHERE C.active = :active AND U.center_id = :centerId
        ORDER BY A.name, U.lastname ";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('active', 1);
        $stmt->bindValue('centerId', $center->getId());
        $stmt->execute();
        return $stmt->fetchAll();
    }
}