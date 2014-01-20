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

    public static function getOverallScores ( $em, $activeCenter, $areas, $period ) {
        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename, U.entry ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'WHERE U.center_id = :center AND S.period = :period ';
        $sql .= 'ORDER BY U.lastname, U.firstname, U.middlename ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('period', $period, "datetime");
        $stmt->bindValue('center', $activeCenter->id, \PDO::PARAM_INT);

        $stmt->execute();
        $students = $stmt->fetchAll();

        if ( !$students ) {
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
        $sql .= 'LEFT JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
        $sql .= 'WHERE S.period = :period AND S.student_id IN ('.implode(',',$studentIds).') ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('period', $period, "datetime");
        $stmt->execute();
        $scores = $stmt->fetchAll();

        if ( !$scores ) {
            return array();
        }

        $scoreIds = array();
        $commentIds = array();
        foreach ( $scores as $score ) {
            $scoreIds[] = $score['id'];
            if ( $score['comment_id'] ) {
                $commentIds[] = $score['comment_id'];
            }
        }

        $commentStandards = array();
        if ( !empty($commentIds) ) {
            // get comment standards
            $sql  = 'SELECT S.id, S.name, CS.comment_id ';
            $sql .= 'FROM cssr_comment_standard CS ';
            $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
            $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
            $sql .= 'ORDER BY CS.comment_id ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $commentStandards = $stmt->fetchAll();
        }

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
                    $student_scores[$student['id']]['scoreCount'] = $scoreCount;
                    $student_scores[$student['id']]['scoreTotal'] = $totalScore;

                    // score stats
                    $student_scores[$student['id']]['scoreStats'] = $scoreStats;

                    // assign rating
                    $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'id' => $score['id'],
                        'name' => $score['area_name'],
                        'value' => $score['value']
                    );

                    if ( $score['comment_id'] ) {
                        $student_scores[$student['id']]['scores'][$score['area_id']]['comment'] = array(
                            'body' => $score['comment'],
                            'updated' => $score['comment_updated'],
                            'updater' => array(
                                'id' => $score['updater_id'],
                                'firstname' => $score['updater_firstname'],
                                'lastname' => $score['updater_lastname']
                            )
                        );

                        foreach ( $commentStandards as $standard ) {
                            if ( $standard['comment_id'] == $score['comment_id'] ) {
                                if ( !isset($student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards']) ) {
                                    $student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards'] = array();
                                }
                                $student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards'][] = $standard['name'];
                            }
                        }
                    }
                }
            }
        }

        return $student_scores;
    }


    public static function getFridayAllComments ( $em, $activeCenter, $areas, $period ) {
        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename, U.entry ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'WHERE U.center_id = '.$activeCenter->id.' AND S.period = "'.$period->format("Y-m-d H:i:s").'" ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        if ( !$students ) {
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
        $sql .= 'WHERE S.period = "'.$period->format("Y-m-d H:i:s").'" AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        if ( !$scores ) {
            return array();
        }

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
                    $student_scores[$student['id']]['scoreTotal'] = $totalScore;
                    $student_scores[$student['id']]['scoreCount'] = $scoreCount;

                    // score stats
                    $student_scores[$student['id']]['scoreStats'] = $scoreStats;

                    // assign rating
                    $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'id' => $score['id'],
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
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename, U.entry ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'WHERE U.center_id = '.$activeCenter->id.' AND S.period = "'.$period->format("Y-m-d H:i:s").'" ';
        $sql .= 'ORDER BY U.lastname, U.firstname, U.middlename ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        if ( !$students ) {
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
        $sql .= 'LEFT JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
        $sql .= 'WHERE S.period = "'.$period->format("Y-m-d H:i:s").'" AND S.student_id IN ('.implode(',',$studentIds).') ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        if ( !$scores ) {
            return array();
        }

        $scoreIds = array();
        $commentIds = array();
        foreach ( $scores as $score ) {
            $scoreIds[] = $score['id'];
            if ( $score['comment_id'] ) {
                $commentIds[] = $score['comment_id'];
            }
        }

        $commentStandards = array();
        if ( !empty($commentIds) ) {
            // get comment standards
            $sql  = 'SELECT S.id, S.name, CS.comment_id ';
            $sql .= 'FROM cssr_comment_standard CS ';
            $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
            $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
            $sql .= 'ORDER BY CS.comment_id ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $commentStandards = $stmt->fetchAll();
        }

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
                    $student_scores[$student['id']]['scoreCount'] = $scoreCount;
                    $student_scores[$student['id']]['scoreTotal'] = $totalScore;

                    // score stats
                    $student_scores[$student['id']]['scoreStats'] = $scoreStats;

                    // assign rating
                    $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'id' => $score['id'],
                        'name' => $score['area_name'],
                        'value' => $score['value']
                    );

                    if ( $score['comment_id'] ) {
                        $student_scores[$student['id']]['scores'][$score['area_id']]['comment'] = array(
                            'body' => $score['comment'],
                            'updated' => $score['comment_updated'],
                            'updater' => array(
                                'id' => $score['updater_id'],
                                'firstname' => $score['updater_firstname'],
                                'lastname' => $score['updater_lastname']
                            )
                        );

                        foreach ( $commentStandards as $standard ) {
                            if ( $standard['comment_id'] == $score['comment_id'] ) {
                                if ( !isset($student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards']) ) {
                                    $student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards'] = array();
                                }
                                $student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards'][] = $standard['name'];
                            }
                        }
                    }
                }
            }
        }

        return $student_scores;
    }

    public static function getFridayCaution ( $em, $activeCenter, $areas, $period ) {
        $reports = array();

        if ( isset($_GET['comments']) ) {
            $allReports = self::getFridayAllComments($em,$activeCenter,$areas,$period);
        } else {
            $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        }

        $total = 0.0;
        $count = 0;
        foreach ( $allReports as $student_id => $report ) {
            // caution
            if ( $report['scoreStats']['2'] == 1 && $report['scoreStats']['1'] == 0 ) {
                $total += $report['avgScore'];
                $count++;
                $reports[$student_id] = $allReports[$student_id];
            }
        }

        if ( $count ) {
            $overallAverage = round($total/$count,2);
        } else {
            $overallAverage = 0.0;
        }

        return array('reports'=>$reports,'overallAverage'=>$overallAverage);
    }

    public static function getFridayChallenge ( $em, $activeCenter, $areas, $period ) {
        $reports = array();

        if ( isset($_GET['comments']) ) {
            $allReports = self::getFridayAllComments($em,$activeCenter,$areas,$period);
        } else {
            $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        }

        $total = 0.0;
        $count = 0;
        foreach ( $allReports as $student_id => $report ) {
            // challenge
            if ( $report['scoreStats']['1'] >= 1 || $report['scoreStats']['2'] >= 2 ) {
                $total += $report['avgScore'];
                $count++;
                $reports[$student_id] = $allReports[$student_id];
            }
        }

        if ( $count ) {
            $overallAverage = round($total/$count,2);
        } else {
            $overallAverage = 0.0;
        }

        return array('reports'=>$reports,'overallAverage'=>$overallAverage);
    }

    public static function getFridayMeetsExpectations ( $em, $activeCenter, $areas, $period ) {
        $reports = array();

        if ( isset($_GET['comments']) ) {
            $allReports = self::getFridayAllComments($em,$activeCenter,$areas,$period);
        } else {
            $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        }

        $total = 0.0;
        $count = 0;
        foreach ( $allReports as $student_id => $report ) {
            // meets expectations
            if ( $report['scoreStats']['1'] == 0 && $report['scoreStats']['2'] == 0 ) {
                $total += $report['avgScore'];
                $count++;
                $reports[$student_id] = $allReports[$student_id];
            }
        }

        if ( $count ) {
            $overallAverage = round($total/$count,2);
        } else {
            $overallAverage = 0.0;
        }

        return array('reports'=>$reports,'overallAverage'=>$overallAverage);    }

    public static function getFriday40 ( $em, $activeCenter, $areas, $period ) {
        $reports = array();

        if ( isset($_GET['comments']) ) {
            $allReports = self::getFridayAllComments($em,$activeCenter,$areas,$period);
        } else {
            $allReports = self::getFridayAll($em,$activeCenter,$areas,$period);
        }

        $total = 0.0;
        $count = 0;
        foreach ( $allReports as $student_id => $report ) {
            // 4.0
            if ( $report['avgScore'] >= 4.0 ) {
                $total += $report['avgScore'];
                $count++;
                $reports[$student_id] = $allReports[$student_id];
            }
        }

        if ( $count ) {
            $overallAverage = round($total/$count,2);
        } else {
            $overallAverage = 0.0;
        }

        return array('reports'=>$reports,'overallAverage'=>$overallAverage);
    }

    public static function getCaseloadScores ( $staff, $em, $activeCenter, $areas, $period ) {

        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename, U.entry ';
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
        $sql  = 'SELECT S.student_id, C.area_id, S.id, S.value, A.name ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'WHERE C.user_id = :staff AND S.period = :period AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('staff', $staff->getId(), \PDO::PARAM_INT);
        $stmt->bindValue('period', $period, 'datetime');
        $stmt->execute();
        $scores = $stmt->fetchAll();

        if ( !$scores ) {
            return array();
        }

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
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'id' => $score['id'],
                        'name' => '',
                        'value' => $score['value'],
                        'comment' => null,
                        'standards' => null
                    );

                    $totalScore += $score['value'];
                    $scoreCount++;

                    foreach ( $scoreStats as $key => $value ) {
                        if ( $score['value'] == $key ) {
                            $scoreStats[$key]++;
                        }
                    }
                }
            }

            if ( $scoreCount ) {
                // calculate average
                $student_scores[$student['id']]['avgScore'] = round($totalScore/$scoreCount,2);
            } else {
                $student_scores[$student['id']]['avgScore'] = 0;
            }

            $student_scores[$student['id']]['scoreCount'] = $scoreCount;
            $student_scores[$student['id']]['scoreTotal'] = $totalScore;

            // score stats
            $student_scores[$student['id']]['scoreStats'] = $scoreStats;

            // assign rating
            $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
        }

        return $student_scores;
    }

    public static function getCaseloadComments ( $staff, $em, $activeCenter, $areas, $period ) {

        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename, U.entry ';
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

        if ( !$scores ) {
            return array();
        }

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
                    $student_scores[$student['id']]['scoreCount'] = $scoreCount;
                    $student_scores[$student['id']]['scoreTotal'] = $totalScore;

                    // score stats
                    $student_scores[$student['id']]['scoreStats'] = $scoreStats;

                    // assign rating
                    $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'id' => $score['id'],
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

        $sql  = 'SELECT U.id, U.firstname, U.lastname, U.middlename, U.entry, S.value, S.period ';
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

        if ( !$scores ) {
            return array();
        }

        $students = array();
        $previous = null;

        foreach ( $scores as $score ) {
            if ( !isset($students[$score['id']] ) ) {
                $students[$score['id']] = array(
                    'id' => $score['id'],
                    'firstname' => $score['firstname'],
                    'lastname' => $score['lastname'],
                    'middlename' => $score['middlename'],
                    'entry' => $score['entry'],
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

    public static function getStudentRecord ( $em, $areas, $studentId ) {

        // find student
        $sql  = 'SELECT U.id, U.firstname, U.lastname, U.middlename ';
        $sql .= 'FROM cssr_user U ';
        $sql .= 'WHERE U.id = '.$studentId.' ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        // scores
        $sql  = 'SELECT S.id, S.student_id, A.id area_id, A.name area_name, S.value, S.period, CM.id comment_id, CM.comment, CM.updated comment_updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'LEFT JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
        $sql .= 'WHERE S.student_id = '.$studentId.' ';
        $sql .= 'ORDER BY S.period ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        if ( !$scores ) {
            return array();
        }

        $commentIds = array();
        foreach ( $scores as $score ) {
            if ( $score['comment_id'] ) {
                $commentIds[] = $score['comment_id'];
            }
        }

        $commentStandards = array();
        if ( !empty($commentIds) ) {
            // get comment standards
            $sql  = 'SELECT S.id, S.name, CS.comment_id ';
            $sql .= 'FROM cssr_comment_standard CS ';
            $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
            $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
            $sql .= 'ORDER BY CS.comment_id ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $commentStandards = $stmt->fetchAll();
        }

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

    public static function getCaseloadStudents ( $em,$staff,$areas,$studentIds ) {

        // find students
        $sql  = 'SELECT U.id, U.firstname, U.lastname, U.middlename, U.entry ';
        $sql .= 'FROM cssr_user U ';
        $sql .= 'WHERE U.id IN ('.$studentIds.') ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        if ( !$students ) {
            return array();
        }

        $studentIds = array();
        foreach ( $students as $student ) {
            $studentIds[] = $student['id'];
        }

        // scores
        $sql  = 'SELECT S.id, S.student_id, A.id area_id, A.name area_name, S.value, S.period, CM.id comment_id, CM.comment, CM.updated comment_updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname, U.entry ';
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

        if ( !$scores ) {
            return array();
        }

        $commentIds = array();
        foreach ( $scores as $score ) {
            if ( $score['comment_id'] ) {
                $commentIds[] = $score['comment_id'];
            }
        }

        $commentStandards = array();
        if ( !empty($commentIds) ) {
            // get comment standards
            $sql  = 'SELECT S.id, S.name, CS.comment_id ';
            $sql .= 'FROM cssr_comment_standard CS ';
            $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
            $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
            $sql .= 'ORDER BY CS.comment_id ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $commentStandards = $stmt->fetchAll();
        }

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
                            'scoreTotal' => null,
                            'scoreCount' => null,
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
                    $student_scores[$student['id']]['scoreCount'] = $scoreCount;
                    $student_scores[$student['id']]['scoreTotal'] = $totalScore;

                    // score stats
                    $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['scoreStats'] = $scoreStats;

                    // assign rating
                    $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['rating'] = self::getRating($student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['avgScore']);
                    $student_scores[$student['id']]['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']] = array(
                        'id' => $score['id'],
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

        return $student_scores;
    }

    public static function getHistoryStudentComments ( $em, $areas, $student ) {

        $studentScores = array(
            'id' => $student->getId(),
            'firstname' => $student->getFirstname(),
            'lastname' => $student->getLastname(),
            'middlename' => $student->getMiddlename(),
            'periods' => array()
        );

        // scores
        $sql  = 'SELECT S.id, S.student_id, A.id area_id, A.name area_name, S.value, S.period, CM.id comment_id, CM.comment, CM.updated comment_updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'INNER JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
        $sql .= 'WHERE S.student_id = :student ';
        $sql .= 'ORDER BY S.period ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('student', $student->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        if ( empty($scores) ) {
            return $studentScores;
        }

        $scoreIds = array();
        $commentIds = array();
        foreach ( $scores as $score ) {
            $scoreIds[] = $score['id'];
            $commentIds[] = $score['comment_id'];
        }

        $commentStandards = array();
        if ( !empty($commentIds) ) {
            // get comment standards
            $sql  = 'SELECT S.id, S.name, CS.comment_id ';
            $sql .= 'FROM cssr_comment_standard CS ';
            $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
            $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
            $sql .= 'ORDER BY CS.comment_id ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $commentStandards = $stmt->fetchAll();
        }

        // populate scores
        foreach ( $scores as $score ) {

            $period = new \DateTime($score['period']);

            if ( empty($studentScores['periods'][$period->format('Y-m-d')]) ) {
                $studentScores['periods'][$period->format('Y-m-d')] = array(
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
                    $studentScores['periods'][$period->format('Y-m-d')]['scores'][$area->getId()] = null;
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
            $studentScores['periods'][$period->format('Y-m-d')]['avgScore'] = round($totalScore/$scoreCount,2);

            // score stats
            $studentScores['periods'][$period->format('Y-m-d')]['scoreStats'] = $scoreStats;

            // assign rating
            $studentScores['periods'][$period->format('Y-m-d')]['rating'] = self::getRating($studentScores['periods'][$period->format('Y-m-d')]['avgScore']);
            $studentScores['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']] = array(
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
                    $studentScores['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']]['standards'][] = $standard['name'];
                }
            }
        }

        //echo '<pre>'.print_r($studentScores,true).'</pre>'; die();

        return $studentScores;
    }

    public static function getHistoryStudent ( $em, $areas, $student ) {

        // scores
        $sql  = 'SELECT S.id, S.student_id, A.id area_id, A.name area_name, S.value, S.period, CM.id comment_id, CM.comment, CM.updated comment_updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'LEFT JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
        $sql .= 'WHERE S.student_id = :student ';
        $sql .= 'ORDER BY S.period ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('student', $student->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        // populate scores
        $studentScores = array(
            'id' => $student->getId(),
            'firstname' => $student->getFirstname(),
            'lastname' => $student->getLastname(),
            'middlename' => $student->getMiddlename(),
            'entry' => $student->getEntry(),
            'periods' => array()
        );

        if ( empty($scores) ) {
            return $studentScores;
        }

        $scoreIds = array();
        $commentIds = array();
        foreach ( $scores as $score ) {
            $scoreIds[] = $score['id'];
            if ( $score['comment_id'] ) {
                $commentIds[] = $score['comment_id'];
            }
        }

        $commentStandards = array();
        if ( !empty($commentIds) ) {
            // get comment standards
            $sql  = 'SELECT S.id, S.name, CS.comment_id ';
            $sql .= 'FROM cssr_comment_standard CS ';
            $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
            $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
            $sql .= 'ORDER BY CS.comment_id ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $commentStandards = $stmt->fetchAll();
        }

        foreach ( $scores as $score ) {

            $period = new \DateTime($score['period']);

            if ( empty($studentScores['periods'][$period->format('Y-m-d')]) ) {
                $studentScores['periods'][$period->format('Y-m-d')] = array(
                    'scores' => array(),
                    'avgScore' => null,
                    'scoreStats' => null,
                    'scoreTotal' => null,
                    'scoreCount' => null,
                    'rating' => null
                );

                // populate scores
                $totalScore = 0;
                $scoreCount = 0;
                $scoreStats = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0);

                // populate all areas
                foreach ( $areas as $area ) {
                    $studentScores['periods'][$period->format('Y-m-d')]['scores'][$area->getId()] = null;
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
            $studentScores['periods'][$period->format('Y-m-d')]['avgScore'] = round($totalScore/$scoreCount,2);
            $studentScores['periods'][$period->format('Y-m-d')]['scoreTotal'] = $totalScore;
            $studentScores['periods'][$period->format('Y-m-d')]['scoreCount'] = $scoreCount;

            // score stats
            $studentScores['periods'][$period->format('Y-m-d')]['scoreStats'] = $scoreStats;

            // assign rating
            $studentScores['periods'][$period->format('Y-m-d')]['rating'] = self::getRating($studentScores['periods'][$period->format('Y-m-d')]['avgScore']);
            $studentScores['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']] = array(
                'id' => $score['id'],
                'name' => $score['area_name'],
                'value' => $score['value']
            );

            if ( $score['comment_id'] ) {
                $studentScores['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']]['comment'] = array(
                    'body' => $score['comment'],
                    'updated' => $score['comment_updated'],
                    'updater' => array(
                        'id' => $score['updater_id'],
                        'firstname' => $score['updater_firstname'],
                        'lastname' => $score['updater_lastname']
                    )
                );

                foreach ( $commentStandards as $standard ) {
                    if ( $standard['comment_id'] == $score['comment_id'] ) {
                        if ( !isset($studentScores['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']]['comment']['standards']) ) {
                            $studentScores['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']]['comment']['standards'] = array();
                        }
                        $studentScores['periods'][$period->format('Y-m-d')]['scores'][$score['area_id']]['comment']['standards'][] = $standard['name'];
                    }
                }
            }
        }

        $total = 0.0;
        $count = 0;
        foreach ( $studentScores['periods'] as $period => $report ) {
            $total += $report['avgScore'];
            $count++;
        }

        if ( $count ) {
            $overallAverage = round($total/$count,2);
        } else {
            $overallAverage = 0.0;
        }

        return array('reports'=>$studentScores,'overallAverage'=>$overallAverage);
    }

    public static function getHistoryStaffScores ( $staff, $em, $activeCenter, $areas, $period ) {

        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename, U.entry ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'WHERE U.center_id = '.$activeCenter->id.' AND C.user_id = '.$staff->getId().' AND S.period = "'.$period->format("Y-m-d H:i:s").'" ';
        $sql .= 'ORDER BY U.lastname, U.firstname, U.middlename ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        if ( !$students ) {
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
        $sql .= 'LEFT JOIN cssr_comment CM ON CM.score_id = S.id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = CM.updated_by ';
        $sql .= 'WHERE S.period = "'.$period->format("Y-m-d H:i:s").'" AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        if ( !$scores ) {
            return array();
        }

        $scoreIds = array();
        $commentIds = array();
        foreach ( $scores as $score ) {
            $scoreIds[] = $score['id'];
            if ( $score['comment_id'] ) {
                $commentIds[] = $score['comment_id'];
            }
        }

        $commentStandards = array();
        if ( !empty($commentIds) ) {
            // get comment standards
            $sql  = 'SELECT S.id, S.name, CS.comment_id ';
            $sql .= 'FROM cssr_comment_standard CS ';
            $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
            $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
            $sql .= 'ORDER BY CS.comment_id ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $commentStandards = $stmt->fetchAll();
        }

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
                    $student_scores[$student['id']]['scores'][$score['area_id']] = array(
                        'id' => $score['id'],
                        'name' => $score['area_name'],
                        'value' => $score['value']
                    );

                    if ( $score['comment_id'] ) {
                        $student_scores[$student['id']]['scores'][$score['area_id']]['comment'] = array(
                            'body' => $score['comment'],
                            'updated' => $score['comment_updated'],
                            'updater' => array(
                                'id' => $score['updater_id'],
                                'firstname' => $score['updater_firstname'],
                                'lastname' => $score['updater_lastname']
                            )
                        );

                        foreach ( $commentStandards as $standard ) {
                            if ( $standard['comment_id'] == $score['comment_id'] ) {
                                if ( !isset($student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards']) ) {
                                    $student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards'] = array();
                                }
                                $student_scores[$student['id']]['scores'][$score['area_id']]['comment']['standards'][] = $standard['name'];
                            }
                        }
                    }

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
            $student_scores[$student['id']]['scoreTotal'] = $totalScore;
            $student_scores[$student['id']]['scoreCount'] = $scoreCount;


            // score stats
            $student_scores[$student['id']]['scoreStats'] = $scoreStats;

            // assign rating
            $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);




        }

        $total = 0.0;
        $count = 0;
        foreach ( $student_scores as $report ) {
            $total += $report['avgScore'];
            $count++;
        }

        if ( $count ) {
            $overallAverage = round($total/$count,2);
        } else {
            $overallAverage = 0.0;
        }

        return array('reports'=>$student_scores,'overallAverage'=>$overallAverage);
    }

    public static function getHistoryStaffComments ( $staff, $em, $activeCenter, $areas, $period ) {

        // find students
        $sql  = 'SELECT S.student_id id, U.firstname, U.lastname, U.middlename ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = S.student_id ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'WHERE U.center_id = '.$activeCenter->id.' AND C.user_id = '.$staff->getId().' AND S.period = "'.$period->format("Y-m-d H:i:s").'" ';
        $sql .= 'ORDER BY S.student_id ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll();

        if ( !$students ) {
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
        $sql .= 'WHERE C.user_id = '.$staff->getId().' AND S.period = "'.$period->format("Y-m-d H:i:s").'" AND S.student_id IN ('.implode(',',$studentIds).') ';
        $sql .= 'ORDER BY S.student_id ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $scores = $stmt->fetchAll();

        if ( !$scores ) {
            return array();
        }

        $scoreIds = array();
        $commentIds = array();
        foreach ( $scores as $score ) {
            $scoreIds[] = $score['id'];
            $commentIds[] = $score['comment_id'];
        }

        $commentStandards = array();
        if ( !empty($commentIds) ) {
            // get comment standards
            $sql  = 'SELECT S.id, S.name, CS.comment_id ';
            $sql .= 'FROM cssr_comment_standard CS ';
            $sql .= 'LEFT JOIN cssr_standard S ON S.id = CS.standard_id ';
            $sql .= 'WHERE CS.comment_id IN ('.implode(',',$commentIds).') ';
            $sql .= 'ORDER BY CS.comment_id ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $commentStandards = $stmt->fetchAll();
        }

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

    public static function getWeeklyStatistics ( $em, $center_id, $periodStart, $periodEnd ) {

        $center = $em->getRepository('CssrMainBundle:Center')->find($center_id);

        $stats = array(
            'center' => $center,
            'caution' => 0,
            'challenge' => 0,
            'great' => 0,
            'expected' => 0
        );

        $sql  = 'SELECT DISTINCT(U.id) ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = student_id ';
        $sql .= 'WHERE U.center_id = :center AND S.period BETWEEN :periodStart AND :periodEnd ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('center', $center_id, \PDO::PARAM_INT);
        $stmt->bindValue('periodStart', $periodStart, 'datetime');
        $stmt->bindValue('periodEnd', $periodEnd, 'datetime');
        $stmt->execute();
        $students = $stmt->fetchAll();

        $sql  = 'SELECT S.student_id, C.area_id, S.value ';
        $sql .= 'FROM cssr_score S ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = S.course_id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = student_id ';
        $sql .= 'WHERE U.center_id = :center AND S.period BETWEEN :periodStart AND :periodEnd ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('center', $center_id, \PDO::PARAM_INT);
        $stmt->bindValue('periodStart', $periodStart, 'datetime');
        $stmt->bindValue('periodEnd', $periodEnd, 'datetime');
        $stmt->execute();
        $scores = $stmt->fetchAll();

        $student_scores = array();
        foreach ( $students as $student ) {
            $student_scores[$student['id']] = $student;
            $student_scores[$student['id']]['scores'] = array();

            // populate scores
            $studentTotalScore = 0;
            $studentScoreCount = 0;
            $studentScoreStats = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0);
            foreach ( $scores as $score ) {
                if ( $score['student_id'] == $student['id'] ) {

                    $studentTotalScore += $score['value'];
                    $studentScoreCount++;

                    foreach ( $studentScoreStats as $key => $value ) {
                        if ( $score['value'] == $key ) {
                            $studentScoreStats[$key]++;
                        }
                    }
                }
            }

            // calculate average
            $student_scores[$student['id']]['avgScore'] = round($studentTotalScore/$studentScoreCount,2);

            // score stats
            $student_scores[$student['id']]['scoreStats'] = $studentScoreStats;

            // assign rating
            $student_scores[$student['id']]['rating'] = self::getRating($student_scores[$student['id']]['avgScore']);
        }

        //echo '<pre>'.print_r($student_scores,true).'</pre>'; die();

        $totalScore = 0;
        $scoreCount = 0;
        foreach ( $student_scores as $score ) {
            $totalScore += $score['avgScore'];
            $scoreCount++;


            // caution
            if ( $score['scoreStats']['2'] == 1 && $score['scoreStats']['1'] == 0 ) {
                $stats['caution']++;
            }

            // challenge
            if ( $score['scoreStats']['1'] >= 1 || $score['scoreStats']['2'] >= 2 ) {
                $stats['challenge']++;
            }

            // meets expectations
            if ( $score['scoreStats']['1'] == 0 && $score['scoreStats']['2'] == 0 ) {
                $stats['expected']++;
            }

            // 4.0
            if ( $score['avgScore'] >= 4.0 ) {
                $stats['great']++;
            }

        }

        $stats['total'] = $scoreCount;
        if ( $stats['total'] ) {
            $stats['avg'] = round($totalScore/$scoreCount,1);

            $stats['greatp'] = round(($stats['great']*100)/$stats['total'],0);
            $stats['expectedp'] = round(($stats['expected']*100)/$stats['total'],0);
            $stats['challengep'] = round(($stats['challenge']*100)/$stats['total'],0);
            $stats['cautionp'] = round(($stats['caution']*100)/$stats['total'],0);
        } else {
            $stats['avg'] = 0;

            $stats['greatp'] = 0;
            $stats['expectedp'] = 0;
            $stats['challengep'] = 0;
            $stats['cautionp'] = 0;
        }

        // average history
        $sql = "
        SELECT score_period, AVG(avg_student_score) score_avg
        FROM (
            SELECT S.period score_period, AVG(S.value) avg_student_score
	        FROM cssr_score S
	        LEFT JOIN cssr_user U ON U.id = S.student_id
	        WHERE U.center_id = :center
	        GROUP BY S.student_id, S.period ) SA
        GROUP BY score_period
        ORDER BY score_period";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('center', $center_id, \PDO::PARAM_INT);
        $stmt->execute();
        $period_avgs = $stmt->fetchAll();
        $stats['period_avgs'] = array();
        foreach ( $period_avgs as $avg ) {
            $stats['period_avgs'][] = array('period'=>$avg['score_period'],'avg'=>round($avg['score_avg'],1));
        }

        return $stats;
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