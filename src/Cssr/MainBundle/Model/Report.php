<?php

namespace Cssr\MainBundle\Model;

class Report {

    public static function getFridayComments ( $em, $activeCenter, $areas, $period ) {
        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'WHERE U.center_id = '.$activeCenter->id.' AND S.period = "'.$period->format("Y-m-d H:i:s").'" ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        $studentIds = array();
        foreach ( $students as $student ) {
            $studentIds[] = $student['id'];
        }

        // scores
        $sql  = 'SELECT S.id, S.student_id, A.id area_id, A.name area_name, S.value, CM.id comment_id, CM.comment ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'INNER JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'WHERE S.period = "'.$period->format("Y-m-d H:i:s").'" AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        $scoreIds = array();
        $commentIds = array();
        foreach ( $scores as $score ) {
            $scoreIds[] = $score['id'];
            $commentIds[] = $score['comment_id'];
        }

        // get comment standards
        $sql  = 'SELECT S.id, S.name, CS.comment_id ';
        $sql .= 'FROM cssr_comment_standard CS ';
        $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
        $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
        $sql .= 'ORDER BY CS.comment_id ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $commentStandards = $stmt->fetchAll();

        $student_scores = array();
        foreach ( $students as $student ) {
            $student_scores[$student['id']] = $student;
            $student_scores[$student['id']]['scores'] = array();

            // populate scores
            foreach ( $scores as $score ) {
                if ( $score['student_id'] == $student['id'] ) {
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'name' => $score['area_name'],
                        'value' => $score['value'],
                        'comment' => $score['comment'],
                        'standards' => array()
                    );

                    foreach ( $commentStandards as $standard ) {
                        if ( $standard['comment_id'] == $score['comment_id'] ) {
                             $student_scores[$student['id']]['scores'][$score['area_id']]['standards'][] = $standard['name'];
                        }
                    }
                }
            }
        }

        return $student_scores;
    }

    public static function getFridayAll ( $em, $activeCenter, $areas, $period ) {

        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'WHERE U.center_id = '.$activeCenter->id.' AND S.period = "'.$period->format("Y-m-d H:i:s").'" ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        $studentIds = array();
        foreach ( $students as $student ) {
            $studentIds[] = $student['id'];
        }

        // scores
        $sql  = 'SELECT S.student_id, C.area_id, S.value ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'WHERE S.period = "'.$period->format("Y-m-d H:i:s").'" AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        $student_scores = array();
        foreach ( $students as $student ) {
            $student_scores[$student['id']] = $student;
            $student_scores[$student['id']]['scores'] = array();

            // populate all areas
            foreach ( $areas as $area ) {
                $student_scores[$student['id']]['scores'][$area->getId()] = null;
            }

            // populate scores
            $totalScore = 0;
            $scoreCount = 0;
            $scoreStats = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0);
            foreach ( $scores as $score ) {
                if ( $score['student_id'] == $student['id'] ) {
                    $student_scores[$student['id']]['scores'][$score['area_id']] = $score['value'];

                    $totalScore += $score['value'];
                    $scoreCount++;

                    foreach ( $scoreStats as $key => $value ) {
                        if ( $score['value'] == $key ) {
                            $scoreStats[$key]++;
                        }
                    }
                }
            }

            // calculate average
            $student_scores[$student['id']]['avgScore'] = round($totalScore/$scoreCount,2);

            // score stats
            $student_scores[$student['id']]['scoreStats'] = $scoreStats;

            // assign rating
            $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
        }

        return $student_scores;
    }

    public static function getFridayCaution ( $em, $activeCenter, $areas, $period ) {
        $reports = null;
        $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        foreach ( $allReports as $student_id => $report ) {
            // caution
            if ( $report['scoreStats']['2'] == 1 && $report['scoreStats']['1'] == 0 ) {
                $reports[$student_id] = $allReports[$student_id];
            }
        }
        return $reports;
    }

    public static function getFridayChallenge ( $em, $activeCenter, $areas, $period ) {
        $reports = null;
        $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        foreach ( $allReports as $student_id => $report ) {
            // challenge
            if ( $report['scoreStats']['1'] >= 1 && $report['scoreStats']['2'] >= 2 ) {
                $reports[$student_id] = $allReports[$student_id];
            }
        }
        return $reports;
    }

    public static function getFridayMeetsExpectations ( $em, $activeCenter, $areas, $period ) {
        $reports = null;
        $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        foreach ( $allReports as $student_id => $report ) {
            // meets expectations
            if ( $report['scoreStats']['1'] == 0 && $report['scoreStats']['2'] == 0 ) {
                $reports[$student_id] = $allReports[$student_id];
            }
        }
        return $reports;
    }

    public static function getFriday40 ( $em, $activeCenter, $areas, $period ) {
        $reports = null;
        $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        foreach ( $allReports as $student_id => $report ) {
            // 4.0
            if ( $report['avgScore'] >= 4.0 ) {
                $reports[$student_id] = $allReports[$student_id];
            }
        }
        return $reports;
    }

    protected static function getRating ( $avgScore ) {
        if ( $avgScore >= 4.3 ) {
            return 'Gold';
        } else if ( $avgScore >= 3.5 ) {
            return 'Green';
        } else if ( $avgScore >= 3 ) {
            return 'Blue';
        } else {
            return null;
        }
    }

}