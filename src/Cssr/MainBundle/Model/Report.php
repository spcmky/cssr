<?php

namespace Cssr\MainBundle\Model;

class Report {

    public static $caseloadNames = array(
        'scores' => 'Caseload Scores',
        'comments' => 'Caseload Comments',
        'esp' => 'ESP Scores',
        'average' => 'Selected Average',
        'students' => 'Student Record'
    );

    public static function getCaseloadReportName ( $key ) {
        if ( isset(self::$caseloadNames[$key]) ) {
            return self::$caseloadNames[$key];
        } else {
            return null;
        }
    }

    public static function getFridayAllComments ( $em, $activeCenter, $areas, $period ) {
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
        $sql  = 'SELECT S.id, S.student_id, A.id area_id, A.name area_name, S.value, CM.id comment_id, CM.comment, CM.updated comment_updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'INNER JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
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

            // populate scores
            $totalScore = 0;
            $scoreCount = 0;
            $scoreStats = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0);

            // populate scores
            foreach ( $scores as $score ) {
                if ( $score['student_id'] == $student['id'] ) {

                    if ( empty($student_scores[$student['id']]) ) {
                        $student_scores[$student['id']] = $student;
                        $student_scores[$student['id']]['scores'] = array();

                        // populate all areas
                        foreach ( $areas as $area ) {
                            $student_scores[$student['id']]['scores'][$area->getId()] = null;
                        }
                    }

                    $totalScore += $score['value'];
                    $scoreCount++;

                    foreach ( $scoreStats as $key => $value ) {
                        if ( $score['value'] == $key ) {
                            $scoreStats[$key]++;
                        }
                    }

                    // calculate average
                    $student_scores[$student['id']]['avgScore'] = round($totalScore/$scoreCount,2);

                    // score stats
                    $student_scores[$student['id']]['scoreStats'] = $scoreStats;

                    // assign rating
                    $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'name' => $score['area_name'],
                        'value' => $score['value'],
                        'comment' => array(
                            'body' => $score['comment'],
                            'updated' => $score['comment_updated'],
                            'updater' => array(
                                'id' => $score['updater_id'],
                                'firstname' => $score['updater_firstname'],
                                'lastname' => $score['updater_lastname']
                            )
                        ),
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

        //echo '<pre>'.print_r($student_scores,true).'</pre>'; die();

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

        if ( isset($_GET['comments']) ) {
            $allReports = self::getFridayAllComments($em,$activeCenter,$areas,$period);
        } else {
            $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        }

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

        if ( isset($_GET['comments']) ) {
            $allReports = self::getFridayAllComments($em,$activeCenter,$areas,$period);
        } else {
            $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        }

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

        if ( isset($_GET['comments']) ) {
            $allReports = self::getFridayAllComments($em,$activeCenter,$areas,$period);
        } else {
            $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        }

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

        if ( isset($_GET['comments']) ) {
            $allReports = self::getFridayAllComments($em,$activeCenter,$areas,$period);
        } else {
            $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        }

        foreach ( $allReports as $student_id => $report ) {
            // 4.0
            if ( $report['avgScore'] >= 4.0 ) {
                $reports[$student_id] = $allReports[$student_id];
            }
        }
        return $reports;
    }

    public static function getCaseloadScores ( $staff, $em, $activeCenter, $areas, $period ) {

        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'WHERE C.user_id = :staff AND U.center_id = :center AND S.period = :period ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('staff', $staff->getId(), \PDO::PARAM_INT);
        $stmt->bindValue('center', $activeCenter->id, \PDO::PARAM_INT);
        $stmt->bindValue('period', $period, 'datetime');

        $stmt->execute();
        $students = $stmt->fetchAll();

        if ( empty($students) ) {
            return array();
        }

        $studentIds = array();
        foreach ( $students as $student ) {
            $studentIds[] = $student['id'];
        }

        // scores
        $sql  = 'SELECT S.student_id, C.area_id, S.value ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'WHERE C.user_id = :staff AND S.period = :period AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('staff', $staff->getId(), \PDO::PARAM_INT);
        $stmt->bindValue('period', $period, 'datetime');
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

    public static function getCaseloadComments ( $staff, $em, $activeCenter, $areas, $period ) {

        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'WHERE C.user_id = :staff AND U.center_id = :center AND S.period = :period ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('staff', $staff->getId(), \PDO::PARAM_INT);
        $stmt->bindValue('center', $activeCenter->id, \PDO::PARAM_INT);
        $stmt->bindValue('period', $period, 'datetime');

        $stmt->execute();
        $students = $stmt->fetchAll();

        if ( empty($students) ) {
            return array();
        }

        $studentIds = array();
        foreach ( $students as $student ) {
            $studentIds[] = $student['id'];
        }

        // scores
        $sql  = 'SELECT S.id, S.student_id, A.id area_id, A.name area_name, S.value, CM.id comment_id, CM.comment, CM.updated comment_updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'INNER JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
        $sql .= 'WHERE C.user_id = :staff AND S.period = :period AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('staff', $staff->getId(), \PDO::PARAM_INT);
        $stmt->bindValue('period', $period, 'datetime');
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

            // populate scores
            $totalScore = 0;
            $scoreCount = 0;
            $scoreStats = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0);

            // populate scores
            foreach ( $scores as $score ) {
                if ( $score['student_id'] == $student['id'] ) {

                    if ( empty($student_scores[$student['id']]) ) {
                        $student_scores[$student['id']] = $student;
                        $student_scores[$student['id']]['scores'] = array();

                        // populate all areas
                        foreach ( $areas as $area ) {
                            $student_scores[$student['id']]['scores'][$area->getId()] = null;
                        }
                    }

                    $totalScore += $score['value'];
                    $scoreCount++;

                    foreach ( $scoreStats as $key => $value ) {
                        if ( $score['value'] == $key ) {
                            $scoreStats[$key]++;
                        }
                    }

                    // calculate average
                    $student_scores[$student['id']]['avgScore'] = round($totalScore/$scoreCount,2);

                    // score stats
                    $student_scores[$student['id']]['scoreStats'] = $scoreStats;

                    // assign rating
                    $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'name' => $score['area_name'],
                        'value' => $score['value'],
                        'comment' => array(
                            'body' => $score['comment'],
                            'updated' => $score['comment_updated'],
                            'updater' => array(
                                'id' => $score['updater_id'],
                                'firstname' => $score['updater_firstname'],
                                'lastname' => $score['updater_lastname']
                            )
                        ),
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

    public static function getCaseloadEsp ( $staff, $em, $activeCenter, $periods ) {

        $sql  = 'SELECT U.id, U.firstname, U.lastname, U.middlename, S.value, S.period ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_user T ON T.id = C.user_id ';
        $sql .= 'WHERE U.center_id = :center AND T.id = :staff AND S.period BETWEEN :periodStart AND :periodEnd ';
        $sql .= 'ORDER BY U.id, S.period ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('staff', $staff->getId(), \PDO::PARAM_INT);
        $stmt->bindValue('center', $activeCenter->id, \PDO::PARAM_INT);
        $stmt->bindValue('periodStart', $periods['start'], 'datetime');
        $stmt->bindValue('periodEnd', $periods['end'], 'datetime');
        $stmt->execute();
        $scores = $stmt->fetchAll();

        $students = array();
        $previous = null;

        foreach ( $scores as $score ) {
            if ( !isset($students[$score['id']] ) ) {
                $students[$score['id']] = array(
                    'id' => $score['id'],
                    'firstname' => $score['firstname'],
                    'lastname' => $score['lastname'],
                    'middlename' => $score['middlename'],
                    'periods' => array()
                );
            }

            $students[$score['id']]['periods'][] = array(
                'date' => $score['period'],
                'score' => $score['value']
            );

        }

        foreach ( $students as $student ) {
            $total = 0;
            $count = 0;
            foreach ( $student['periods'] as $period ) {
                $count++;
                $total += $period['score'];
            }
            $students[$student['id']]['avgScore'] = ($count) ? round($total/$count,2) : 0;
            $students[$student['id']]['rating'] = self::getRating($students[$student['id']]['avgScore']);
        }

        return $students;
    }

    public static function getCaseloadStudents ( $em,$staff,$areas,$studentIds ) {

        // find students
        $sql  = 'SELECT U.id, U.firstname, U.lastname, U.middlename ';
        $sql .= 'FROM cssr_user U ';
        $sql .= 'WHERE U.id IN ('.$studentIds.') ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        $studentIds = array();
        foreach ( $students as $student ) {
            $studentIds[] = $student['id'];
        }

        // scores
        $sql  = 'SELECT S.id, S.student_id, A.id area_id, A.name area_name, S.value, S.period, CM.id comment_id, CM.comment, CM.updated comment_updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'LEFT JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
        $sql .= 'WHERE C.user_id = :staff AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id, S.period ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('staff', $staff->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        $commentIds = array();
        foreach ( $scores as $score ) {
            if ( $score['comment_id'] ) {
                $commentIds[] = $score['comment_id'];
            }
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


            // populate scores
            foreach ( $scores as $score ) {

                if ( $score['student_id'] == $student['id'] ) {

                    if ( empty($student_scores[$student['id']]) ) {
                        $student_scores[$student['id']] = $student;

                        $student_scores[$student['id']]['periods'] = array();
                    }

                    $period = new \DateTime($score['period']);

                    if ( empty($student_scores[$student['id']]['periods'][$period->format('Y-m-d')]) ) {
                        $student_scores[$student['id']]['periods'][$period->format('Y-m-d')] = array(
                            'scores' => array(),
                            'avgScore' => null,
                            'scoreStats' => null,
                            'rating' => null
                        );

                        // populate scores
                        $totalScore = 0;
                        $scoreCount = 0;
                        $scoreStats = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0);

                        // populate all areas
                        foreach ( $areas as $area ) {
                            $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['scores'][$area->getId()] = null;
                        }
                    }

                    $totalScore += $score['value'];
                    $scoreCount++;

                    foreach ( $scoreStats as $key => $value ) {
                        if ( $score['value'] == $key ) {
                            $scoreStats[$key]++;
                        }
                    }

                    // calculate average
                    $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['avgScore'] = round($totalScore/$scoreCount,2);

                    // score stats
                    $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['scoreStats'] = $scoreStats;

                    // assign rating
                    $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['rating'] = self::getRating($student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['avgScore']);
                    $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']] = array(
                        'name' => $score['area_name'],
                        'value' => $score['value'],
                        'comment' => array(
                            'body' => $score['comment'],
                            'updated' => $score['comment_updated'],
                            'updater' => array(
                                'id' => $score['updater_id'],
                                'firstname' => $score['updater_firstname'],
                                'lastname' => $score['updater_lastname']
                            )
                        ),
                        'standards' => array()
                    );

                    foreach ( $commentStandards as $standard ) {
                        if ( $standard['comment_id'] == $score['comment_id'] ) {
                            $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']]['standards'][] = $standard['name'];
                        }
                    }

                }

            }

        }

        //echo '<pre>'.print_r($student_scores,true).'</pre>'; die();

        return $student_scores;
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