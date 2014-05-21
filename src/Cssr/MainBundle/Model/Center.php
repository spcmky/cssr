<?php

namespace Cssr\MainBundle\Model;

class Center {

    public static function getCourses ( $em, $center ) {

        $sql  = 'SELECT C.id, C.area_id, C.user_id, A.name, U.firstname, U.lastname ';
        $sql .= 'FROM cssr_course C ';
        $sql .= 'INNER JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = C.user_id ';
        $sql .= 'WHERE C.active = :active AND U.center_id = :centerId ';
        $sql .= 'ORDER BY A.name, U.lastname ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('active', 1);
        $stmt->bindValue('centerId', $center->getId());
        $stmt->execute();
        return $stmt->fetchAll();
    }
}