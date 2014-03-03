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

    public static function getStudents ( $em, $staffId ) {
        $sql  = 'SELECT S.id, S.firstname, S.lastname, S.middlename ';
        $sql .= 'FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_student_course SC ON SC.course_id = C.id ';
        $sql .= 'LEFT JOIN cssr_user S ON S.id = SC.student_id ';
        $sql .= 'WHERE C.user_id = :userId AND C.active = :active AND SC.enrolled = :enrolled AND S.enabled = :enabled ';
        $sql .= 'ORDER BY S.lastname, S.firstname, S.middlename ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $staffId);
        $stmt->bindValue('active', 1, \PDO::PARAM_INT);
        $stmt->bindValue('enrolled', 1, \PDO::PARAM_INT);
        $stmt->bindValue('enabled', 1, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getStaffWithCaseload ( $em, $centerId, $period ) {

        // find staff with courses
        $sql  = 'SELECT U.id, U.lastname, U.firstname, U.middlename, C.id AS course_id, A.name AS course_name ';
        $sql .= 'FROM cssr_user U ';
        $sql .= 'LEFT JOIN cssr_user_group UG ON UG.user_id = U.id ';
        $sql .= 'LEFT JOIN cssr_group G ON G.id = UG.group_id ';
        $sql .= 'INNER JOIN cssr_course C ON C.user_id = U.id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'WHERE U.enabled = :enabled AND G.id = :group AND U.center_id = :center AND C.active = :active ';
        $sql .= 'ORDER BY U.lastname, U.firstname ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('enabled', 1, \PDO::PARAM_INT);
        $stmt->bindValue('group', 5, \PDO::PARAM_INT);
        $stmt->bindValue('center', $centerId, \PDO::PARAM_INT);
        $stmt->bindValue('active', 1, \PDO::PARAM_INT);
        $stmt->execute();
        $staff = $stmt->fetchAll();

        // find those with enrolled students
        $staff_students = array();
        foreach ( $staff as $s ) {
            $students = self::getStudents($em,$s['id']);
            if ( $students ) {
                $staff_students[$s['id']] = $s;
                $staff_students[$s['id']]['students'] = $students;
            }
        }

        // find those with scores for a period
        foreach ( $staff_students as $ss ) {

            $sql  = 'SELECT COUNT(id) ';
            $sql .= 'FROM cssr_score ';
            $sql .= 'WHERE course_id = :courseId AND period = :period ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('courseId', $ss['course_id'], \PDO::PARAM_INT);
            $stmt->bindValue('period', new \DateTime($period), "datetime");
            $stmt->execute();
            $scoreCount = $stmt->fetchColumn();

            $staff_students[$ss['id']]['scoreCount'] = $scoreCount;
        }

        return $staff_students;


    }
} 