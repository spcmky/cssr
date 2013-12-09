<?php

namespace Cssr\MainBundle\Model;


class Student {

    public static function getCourses ( $em, $student ) {

        $sql = "
        SELECT C.id, C.area_id, C.user_id, A.name, U.firstname, U.lastname
        FROM cssr_student_course UC
        LEFT JOIN cssr_course C ON C.id = UC.course_id
        LEFT JOIN cssr_area A ON A.id = C.area_id
        LEFT JOIN cssr_user U ON U.id = C.user_id
        WHERE UC.student_id = :userId AND UC.enrolled = :enrolled ";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $student->getId());
        $stmt->bindValue('enrolled', 1, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function enroll ( $em, $student, $courses ) {

        $currentCourses = array();
        foreach ( self::getCourses($em,$student) as $c ) {
            $currentCourses[] = $c['id'];
        }

        // what to remove
        $removed = array_diff($currentCourses,$courses);
        foreach ( $removed as $courseId ) {
            $sql  = 'UPDATE cssr_student_course ';
            $sql .= 'SET enrolled = :enrolled, updated = :updated ';
            $sql .= 'WHERE student_id = :student AND course_id = :course ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('enrolled', 0, \PDO::PARAM_INT);
            $stmt->bindValue('updated', new \DateTime(), 'datetime');
            $stmt->bindValue('student', $student->getId(), \PDO::PARAM_INT);
            $stmt->bindValue('course', $courseId, \PDO::PARAM_INT);
            $stmt->execute();
        }

        // what to add
        $added = array_diff($courses, $currentCourses);
        foreach ( $added as $courseId ) {

            // check for previous enrollment
            $sql = 'SELECT 1 FROM cssr_student_course WHERE student_id = :student AND course_id = :course';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('student', $student->getId(), \PDO::PARAM_INT);
            $stmt->bindValue('course', $courseId, \PDO::PARAM_INT);
            $stmt->execute();
            $hasPrevious = $stmt->fetch();

            if ( $hasPrevious ) {
                $sql  = 'UPDATE cssr_student_course ';
                $sql .= 'SET enrolled = :enrolled, updated = :updated ';
                $sql .= 'WHERE student_id = :student AND course_id = :course ';
                $stmt = $em->getConnection()->prepare($sql);
                $stmt->bindValue('enrolled', 1, \PDO::PARAM_INT);
                $stmt->bindValue('updated', new \DateTime(), 'datetime');
                $stmt->bindValue('student', $student->getId(), \PDO::PARAM_INT);
                $stmt->bindValue('course', $courseId, \PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $sql  = 'INSERT INTO cssr_student_course ( course_id, student_id, enrolled, updated, created ) ';
                $sql .= 'VALUES ( :course, :student, :enrolled, :updated, :created ) ';
                $stmt = $em->getConnection()->prepare($sql);
                $stmt->bindValue('enrolled', 1, \PDO::PARAM_INT);
                $stmt->bindValue('updated', new \DateTime(), 'datetime');
                $stmt->bindValue('created', new \DateTime(), 'datetime');
                $stmt->bindValue('student', $student->getId(), \PDO::PARAM_INT);
                $stmt->bindValue('course', $courseId, \PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
}