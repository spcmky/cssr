<?php

namespace Cssr\MainBundle\Model;


class Staff {

    public static function getCourses ( $em, $staff ) {
        $sql  = 'SELECT C.id, C.area_id, C.user_id, A.name, U.firstname, U.lastname ';
        $sql .= 'FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = C.user_id ';
        $sql .= 'WHERE C.user_id = :userId AND C.active = :active ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $staff->getId());
        $stmt->bindValue('active', 1, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function updateCourses ( $em, $staff, $courses ) {
        $currentCourses = array();
        foreach ( self::getCourses($em,$staff) as $c ) {
            $currentCourses[] = $c['area_id'];
        }

        // what to remove
        $removed = array_diff($currentCourses,$courses);
        foreach ( $removed as $areaId ) {
            $sql  = 'UPDATE cssr_course ';
            $sql .= 'SET active = :active, updated = :updated ';
            $sql .= 'WHERE user_id = :staffId AND area_id = :areaId ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('active', 0, \PDO::PARAM_INT);
            $stmt->bindValue('updated', new \DateTime(), 'datetime');
            $stmt->bindValue('staffId', $staff->getId(), \PDO::PARAM_INT);
            $stmt->bindValue('areaId', $areaId, \PDO::PARAM_INT);
            $stmt->execute();
        }

        // what to add
        $added = array_diff($courses, $currentCourses);
        foreach ( $added as $areaId ) {

            // check for previous course
            $sql = 'SELECT 1 FROM cssr_course WHERE user_id = :staffId AND area_id = :areaId';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('staffId', $staff->getId(), \PDO::PARAM_INT);
            $stmt->bindValue('areaId', $areaId, \PDO::PARAM_INT);
            $stmt->execute();
            $hasPrevious = $stmt->fetch();

            if ( $hasPrevious ) {
                $sql  = 'UPDATE cssr_course ';
                $sql .= 'SET active = :active, updated = :updated ';
                $sql .= 'WHERE user_id = :staffId AND area_id = :areaId ';
                $stmt = $em->getConnection()->prepare($sql);
                $stmt->bindValue('active', 1, \PDO::PARAM_INT);
                $stmt->bindValue('updated', new \DateTime(), 'datetime');
                $stmt->bindValue('staffId', $staff->getId(), \PDO::PARAM_INT);
                $stmt->bindValue('areaId', $areaId, \PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $sql  = 'INSERT INTO cssr_course ( user_id, area_id, active, updated, created ) ';
                $sql .= 'VALUES ( :staffId, :areaId, :active, :updated, :created ) ';
                $stmt = $em->getConnection()->prepare($sql);
                $stmt->bindValue('active', 1, \PDO::PARAM_INT);
                $stmt->bindValue('updated', new \DateTime(), 'datetime');
                $stmt->bindValue('created', new \DateTime(), 'datetime');
                $stmt->bindValue('staffId', $staff->getId(), \PDO::PARAM_INT);
                $stmt->bindValue('areaId', $areaId, \PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }

    public static function getStudents ( $em, $staff ) {
        $sql  = 'SELECT S.id, S.firstname, S.lastname, S.middlename ';
        $sql .= 'FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_student_course SC ON SC.course_id = C.id ';
        $sql .= 'LEFT JOIN cssr_user S ON S.id = SC.student_id ';
        $sql .= 'WHERE C.user_id = :userId AND C.active = :active AND SC.enrolled = :enrolled AND S.enabled = :enabled ';
        $sql .= 'ORDER BY S.lastname, S.firstname, S.middlename ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $staff->getId());
        $stmt->bindValue('active', 1, \PDO::PARAM_INT);
        $stmt->bindValue('enrolled', 1, \PDO::PARAM_INT);
        $stmt->bindValue('enabled', 1, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
} 