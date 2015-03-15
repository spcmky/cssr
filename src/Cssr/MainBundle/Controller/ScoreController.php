<?php

namespace Cssr\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Cssr\MainBundle\Entity\Score;
use Cssr\MainBundle\Form\ScoreType;
use Cssr\MainBundle\Model\Report;
use Cssr\MainBundle\Model\Student;
use Cssr\MainBundle\Model\Center;
use Cssr\MainBundle\Model\Group;


/**
 * Score controller.
 *
 * @Route("/score")
 */
class ScoreController extends Controller
{

    /**
     * Lists all Score entities.
     *
     * @Route("/", name="score")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * Lists all Score entities.
     *
     * @Route("/overall", name="score_overall")
     * @Method("GET")
     * @Template()
     */
    public function overallAction()
    {
        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');

        $em = $this->getDoctrine()->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();
        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        $sql = 'SELECT DISTINCT(period) period FROM cssr_score ORDER BY period ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $periods = array();
        foreach ( $stmt->fetchAll() as $p ) {
            $periods[] = new \DateTime($p['period']);
        }
        $periods = array_reverse($periods);
        $periods = array_slice($periods,0,10);
        $periods = array_reverse($periods);

        if ( isset($_GET['period']) ) {
            $period = new \DateTime($_GET['period']);
        } else {
            $period = $periods[count($periods)-1];
        }

        $period_start = clone $period;
        $period_start->sub(new \DateInterval('P1D'));

        $period_end = clone $period;
        $period_end->add(new \DateInterval('P5D'));

        $reports = Report::getOverallScores($em,$activeCenter,$areas,$period);

        $total = 0.0;
        $count = 0;
        foreach ( $reports as $report ) {
            $total += $report['avgScore'];
            $count++;
        }

        if ( $count ) {
            $overallAverage = round($total/$count,2);
        } else {
            $overallAverage = 0.0;
        }

        $user = $this->getUser();
        $isStudent = 0;
        foreach ( $user->getGroups() as $group ) {
            if ( $group->getId() == 6 ) {
                $isStudent = 1;
            }
        }

        $vars = array(
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'areas' => $areas,
            'standards' => $standards,
            'reports' => $reports,
            'overallAverage' => $overallAverage,
            'isStudent' => $isStudent
        );

        if ( isset($_GET['type']) ) {
            $vars['type'] = $_GET['type'];
        }

        return $vars;
    }

    /**
     * Lists student entities.
     *
     * @Route("/student", name="score_student")
     * @Method("GET")
     * @Template()
     */
    public function studentIndexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        if ( $center ) {
            $sql  = 'SELECT U.* ';
            $sql .= 'FROM cssr_user_group UG ';
            $sql .= 'LEFT JOIN cssr_user U ON U.id = UG.user_id ';
            $sql .= 'WHERE U.center_id = :centerId AND UG.group_id = :groupId AND U.enabled = :enabled ';
            $sql .= 'ORDER BY U.lastname, U.firstname, U.middlename ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('centerId', $center->id);
            $stmt->bindValue('groupId', 6);
            $stmt->bindValue('enabled', 1);
            $stmt->execute();
            $result = $stmt->fetchAll();
        } else {
            $sql  = 'SELECT U.* ';
            $sql .= 'FROM cssr_user_group UG ';
            $sql .= 'LEFT JOIN cssr_user U ON U.id = UG.user_id ';
            $sql .= 'WHERE UG.group_id = :groupId AND U.enabled = :enabled ';
            $sql .= 'ORDER BY U.lastname, U.firstname, U.middlename ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('groupId', 6);
            $stmt->bindValue('enabled', 1);
            $stmt->execute();
            $result = $stmt->fetchAll();
        }

        return array(
            'entities' => $result
        );
    }

    /**
     * Lists scores for a student.
     *
     * @Route("/student/{id}", name="score_student_show")
     * @Method("GET")
     * @Cache(maxage="0", smaxage="0", expires="now", public="false")
     * @Template()
     */
    public function studentScoreAction ( Request $request, $id )
    {
        $em = $this->getDoctrine()->getManager();

        $student = $em->getRepository('CssrMainBundle:User')->find($id);

        if ( !$student ) {
            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'failed';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                throw $this->createNotFoundException('Unable to find Student entity.');
            }
        }

        $courses = Student::getCourses($em,$student);

        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        // calculate current week and last completed week
        $today = new \DateTime(date('Y-m-d'));

        if ( $today->format('w') < 6 || ($today->format('w') == 6 && $today->format('H') < 14 ) ) {
            $offset = 0 - $today->format('w');
        } else {
            $offset = 7 - $today->format('w');
        }

        $period_current = new \DateTime(date('Y-m-d'));
        if ( $offset > 0 ) {
            $period_current->add(new \DateInterval('P'.$offset.'D'));
        } else if ( $offset < 0 )   {
            $period_current->sub(new \DateInterval('P'.abs($offset).'D'));
        }

        $period_last = clone $period_current;
        $offset = $offset - 7;
        $period_last->sub(new \DateInterval('P'.abs($offset).'D'));

        $periods = array(
            $period_last,
            $period_current
        );

        if ( isset($_GET['period']) ) {
            $period = new \DateTime($_GET['period']);
        } else {
            if ( !empty($periods) ) {
                $period = $periods[count($periods)-1];
            } else {
                $period = new \DateTime(date('Y-m-d'));
            }
        }

        $period_start = clone $period;
        $period_start->sub(new \DateInterval('P1D'));

        $period_end = clone $period;
        $period_end->add(new \DateInterval('P5D'));

        $scores = array();
        if ( $periods ) {
            $sql = 'SELECT * FROM cssr_score WHERE student_id = :studentId AND period = :period ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue("studentId",$id,\PDO::PARAM_INT);
            $stmt->bindValue('period', $period, "datetime");
            $stmt->execute();
            $scores = $stmt->fetchAll();
        }

        $scoreIds = array();
        foreach ( $scores as $s ) {
            $scoreIds[] = $s['id'];
        }
        if ( !empty($scoreIds) ) {
            $sql = "SELECT C.id, C.comment, C.score_id, C.updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname
            FROM cssr_comment C
            LEFT JOIN cssr_user U ON U.id = C.updated_by
            WHERE score_id IN (".implode(',',$scoreIds).") ";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $comments = $stmt->fetchAll();
        }

        if ( !empty($comments) ) {
            foreach ( $comments as $c ) {
                $commentIds[] = $c['id'];
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
        }


        $student_scores = array();
        foreach ( $courses as $course ) {

            $student_scores[$course['id']] = array(
                'course' => array(
                    'id' => $course['id'],
                    'name' => $course['name']
                ),
                'score' => array(
                    'id' => null,
                    'value' => null
                ),
                'comment' => null
            );

            foreach ( $scores as $score ) {
                if ( $score['course_id'] == $course['id'] ) {
                    $student_scores[$course['id']]['score']['value'] = $score['value'];
                    $student_scores[$course['id']]['score']['id'] = $score['id'];

                    foreach ( $comments as $comment ) {
                        if ( $comment['score_id'] == $score['id'] ) {
                            $student_scores[$course['id']]['comment'] = array(
                                'id' => $comment['id'],
                                'body' => $comment['comment'],
                                'updated' => $comment['updated'],
                                'updater' => array(
                                    'id' => $comment['updater_id'],
                                    'firstname' => $comment['updater_firstname'],
                                    'lastname' => $comment['updater_lastname']
                                ),
                                'standards' => array()
                            );

                            foreach ( $commentStandards as $standard ) {
                                if ( $standard['comment_id'] == $comment['id'] ) {
                                    $student_scores[$course['id']]['comment']['standards'][] = $standard['name'];
                                }
                            }
                        }
                    }
                }
            }
        }

        $session = $this->getRequest()->getSession();
        $activeCenter = $session->get('center');
        $center = $em->getRepository('CssrMainBundle:Center')->find($activeCenter->id);

        $centerCourses = Center::getCourses($em,$center);

        $groupedCourses = array();
        foreach ( $centerCourses as $centerCourse ) {
            if ( !isset($groupedCourses[$centerCourse['name']]) ) {
                $groupedCourses[$centerCourse['name']] = array(
                    'name' => $centerCourse['name'],
                    'courses' => array()
                );
            }

            $groupedCourses[$centerCourse['name']]['courses'][$centerCourse['id']] = array(
                'id' => $centerCourse['id'],
                'firstname' => $centerCourse['firstname'],
                'lastname' => $centerCourse['lastname']
            );
        }

        // remove areas already enrolled in
        foreach ( $courses as $studentCourse ) {
            if ( isset($groupedCourses[$studentCourse['name']]) ) {
                unset($groupedCourses[$studentCourse['name']]);
            }
        }

        $user = $this->getUser();
        $isStudent = 0;
        foreach ( $user->getGroups() as $group ) {
            if ( $group->getId() == 6 ) {
                $isStudent = 1;
            }
        }

        $data = array(
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'student' => $student,
            'standards' => $standards,
            'scores' => $student_scores,
            'user' => $this->getUser(),
            'availableCourses' => $groupedCourses,
            'isStudent' => $isStudent
        );

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'success';

            $view = $this->render('CssrMainBundle:Score:commentsModal.html.twig',$data);
            $api_response->data = $view->getContent();

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            return $data;
        }
    }

    /**
     * Lists all Score entities.
     *
     * @Route("/staff", name="score_staff")
     * @Method("GET")
     * @Template()
     */
    public function staffIndexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->getRequest()->getSession();
        $center = $session->get('center');

        if ( $center ) {
            $sql  = 'SELECT U.* ';
            $sql .= 'FROM cssr_user_group UG ';
            $sql .= 'LEFT JOIN cssr_user U ON U.id = UG.user_id ';
            $sql .= 'WHERE U.center_id = :centerId AND UG.group_id = :groupId AND U.enabled = :enabled ';
            $sql .= 'ORDER BY U.lastname, U.firstname, U.middlename ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('centerId', $center->id);
            $stmt->bindValue('groupId', 5);
            $stmt->bindValue('enabled', 1);
            $stmt->execute();
            $result = $stmt->fetchAll();
        } else {
            $sql  = 'SELECT U.* ';
            $sql .= 'FROM cssr_user_group UG ';
            $sql .= 'LEFT JOIN cssr_user U ON U.id = UG.user_id ';
            $sql .= 'WHERE UG.group_id = :groupId AND U.enabled = :enabled ';
            $sql .= 'ORDER BY U.lastname, U.firstname, U.middlename ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('groupId', 5);
            $stmt->bindValue('enabled', 1);
            $stmt->execute();
            $result = $stmt->fetchAll();
        }

        return array(
            'entities' => $result
        );
    }

    /**
     * Lists scores for a staffer.
     *
     * @Route("/staff/{id}", name="score_staff_show")
     * @Method("GET")
     * @Template()
     */
    public function staffScoreAction ( $id ) {
        $em = $this->getDoctrine()->getManager();

        $staff = $em->getRepository('CssrMainBundle:User')->find($id);

        if ( !$staff ) {
            throw $this->createNotFoundException('Unable to find Staff entity.');
        }

        $standards = $em->getRepository('CssrMainBundle:Standard')->findAll();

        // calculate current week and last completed week
        $today = new \DateTime(date('Y-m-d'));

        if ( $today->format('w') < 6 || ($today->format('w') == 6 && $today->format('H') < 14 ) ) {
            $offset = 0 - $today->format('w');
        } else {
            $offset = 7 - $today->format('w');
        }

        $period_current = new \DateTime(date('Y-m-d'));
        if ( $offset > 0 ) {
            $period_current->add(new \DateInterval('P'.$offset.'D'));
        } else if ( $offset < 0 )   {
            $period_current->sub(new \DateInterval('P'.abs($offset).'D'));
        }

        $period_last = clone $period_current;
        $offset = $offset - 7;
        $period_last->sub(new \DateInterval('P'.abs($offset).'D'));

        $periods = array(
            $period_last,
            $period_current
        );

        if ( isset($_GET['period']) ) {
            $period = new \DateTime($_GET['period']);
        } else {
            if ( !empty($periods) ) {
                $period = $periods[count($periods)-1];
            } else {
                $period = new \DateTime(date('Y-m-d'));
            }
        }

        $period_start = clone $period;
        $period_start->sub(new \DateInterval('P1D'));

        $period_end = clone $period;
        $period_end->add(new \DateInterval('P5D'));

        $sql  = 'SELECT C.id, A.name ';
        $sql .= 'FROM cssr_course C ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'WHERE C.user_id = :userId AND C.active = :active ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('userId', $id);
        $stmt->bindValue('active', 1, \PDO::PARAM_INT);
        $stmt->execute();
        $courses = $stmt->fetchAll();

        if ( empty($courses) ) {
            return array(
                'user' => $this->getUser(),
                'period' => $period,
                'period_start' => $period_start,
                'period_end' => $period_end,
                'periods' => $periods,
                'scores' => array(),
                'standards' => $standards,
                'staff' => $staff
            );
        }

        $courseIds = array();
        foreach ( $courses as $c ) {
            $courseIds[] = $c['id'];
        }

        $sql  = 'SELECT C.id, A.id area_id, A.name area_name, U.id student_id, U.firstname user_firstname, U.lastname user_lastname, U.middlename user_middlename ';
        $sql .= 'FROM cssr_student_course SC ';
        $sql .= 'LEFT JOIN cssr_course C ON C.id = SC.course_id ';
        $sql .= 'LEFT JOIN cssr_area A ON A.id = C.area_id ';
        $sql .= 'LEFT JOIN cssr_user U ON U.id = SC.student_id ';
        $sql .= 'WHERE SC.course_id IN ('.implode(',',$courseIds).') AND U.enabled = :enabled AND SC.enrolled = :enrolled ';
        $sql .= 'ORDER BY area_name, U.lastname, U.firstname, U.middlename ';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('enabled', 1, \PDO::PARAM_INT);
        $stmt->bindValue('enrolled', 1, \PDO::PARAM_INT);

        $stmt->execute();
        $student_courses = $stmt->fetchAll();

        $scores = null;
        if ( $periods ) {
            $sql = "SELECT * FROM cssr_score WHERE course_id in (".implode(',',$courseIds).") AND period = '".$period->format("Y-m-d H:i:s")."'";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $scores = $stmt->fetchAll();
        }

        $scoreIds = array();
        foreach ( $scores as $s ) {
            $scoreIds[] = $s['id'];
        }
        if ( !empty($scoreIds) ) {
            $sql = "SELECT C.id, C.comment, C.score_id, C.updated, U.id updater_id, U.firstname updater_firstname, U.lastname updater_lastname
            FROM cssr_comment C
            LEFT JOIN cssr_user U ON U.id = C.updated_by
            WHERE score_id IN (".implode(',',$scoreIds).") ";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $comments = $stmt->fetchAll();
        }

        if ( !empty($comments) ) {
            foreach ( $comments as $c ) {
                $commentIds[] = $c['id'];
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
        }

        $student_scores = array();
        foreach ( $student_courses as $course ) {

            $student_scores[$course['student_id']] = array(
                'student' => array(
                    'id' => $course['student_id'],
                    'firstname' => $course['user_firstname'],
                    'middlename' => $course['user_middlename'],
                    'lastname' => $course['user_lastname']
                ),
                'course' => array(
                    'id' => $course['id'],
                    'name' => $course['area_name']
                ),
                'score' => array(
                    'id' => null,
                    'value' => null
                ),
                'comment' => null
            );

            foreach ( $scores as $score ) {
                if ( $score['course_id'] == $course['id'] && $score['student_id'] == $course['student_id'] ) {
                    $student_scores[$course['student_id']]['score']['value'] = $score['value'];
                    $student_scores[$course['student_id']]['score']['id'] = $score['id'];

                    foreach ( $comments as $comment ) {
                        if ( $comment['score_id'] == $score['id'] ) {
                            $student_scores[$course['student_id']]['comment'] = array(
                                'id' => $comment['id'],
                                'body' => $comment['comment'],
                                'updated' => $comment['updated'],
                                'updater' => array(
                                    'id' => $comment['updater_id'],
                                    'firstname' => $comment['updater_firstname'],
                                    'lastname' => $comment['updater_lastname']
                                ),
                                'standards' => array()
                            );

                            foreach ( $commentStandards as $standard ) {
                                if ( $standard['comment_id'] == $comment['id'] ) {
                                    $student_scores[$course['student_id']]['comment']['standards'][] = $standard['name'];
                                }
                            }
                        }
                    }
                }
            }
        }


        return array(
            'user' => $this->getUser(),
            'period' => $period,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'periods' => $periods,
            'scores' => $student_scores,
            'standards' => $standards,
            'staff' => $staff
        );
    }

    /**
     * Creates a new Score entity.
     *
     * @Route("/", name="score_create")
     * @Method("POST")
     * @Template("CssrMainBundle:Score:new.html.twig")
     */
    public function createAction ( Request $request ) {

        if (  !Group::isGranted($this->getUser(),'score admin') && !Group::isGranted($this->getUser(),'score update') ) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        $em = $this->getDoctrine()->getManager();

        $score  = new Score();
        $form = $this->createForm(new ScoreType(), $score);
        $form->submit($request);

        $isValid = true;
        if ( !empty($_POST['value']) ) {
            $value = $_POST['value'];
            if ( $value == 'N/A' ) {
                $value = null;
                $score->setValue($value);
            } else if ( in_array($value,array(1,2,3,4,5)) ) {
                $value = (int) $value;
                $score->setValue($value);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( !empty($_POST['student']) ) {
            $student = $em->getRepository('CssrMainBundle:User')->find($_POST['student']);
            if ( $student ) {
                $score->setStudent($student);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( !empty($_POST['course']) ) {
            $course = $em->getRepository('CssrMainBundle:Course')->find($_POST['course']);
            if ( $course ) {
                $score->setCourse($course);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( !empty($_POST['period']) ) {
            $period = new \DateTime($_POST['period']);
            $score->setPeriod($period);
        } else {
            $isValid = false;
        }

        //{"value":"5","student":"49446","course":"91","period":"2013-10-27"}

        if ( $isValid ) {

            // search for an existing score based on student, course, and period
            $sql = 'SELECT S.id ';
            $sql .= 'FROM cssr_score S ';
            $sql .= 'WHERE S.period = :period AND S.student_id = :studentId AND S.course_id = :courseId ';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('studentId', $student->getId(), \PDO::PARAM_INT);
            $stmt->bindValue('courseId', $course->getId(), \PDO::PARAM_INT);
            $stmt->bindValue('period', $period, "datetime");
            $stmt->execute();
            $existingScore = $stmt->fetch(\PDO::FETCH_OBJ);

            // if score exists, update it
            if ( $existingScore ) {
                $score = $em->getRepository('CssrMainBundle:Score')->find($existingScore->id);
                $score->setValue($value);
            }

            $score->setCreatedBy($this->getUser());
            $score->setUpdatedBy($this->getUser());

            if ( !$existingScore ) {
                $em->persist($score);
            }
            $em->flush();

            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'success';
                $api_response->scoreId = $score->getId();

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                return $this->redirect($this->generateUrl('score_show', array('id' => $score->getId())));
            }
        }

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';
            $api_response->data = $_POST;


            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return array(
            'entity' => $score,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Score entity.
     *
     * @Route("/new", name="score_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        if (  !Group::isGranted($this->getUser(),'score admin') && !Group::isGranted($this->getUser(),'score update') ) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        $entity = new Score();
        $form   = $this->createForm(new ScoreType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Score entity.
     *
     * @Route("/{id}", name="score_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Score')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Score entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Score entity.
     *
     * @Route("/{id}/edit", name="score_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        if (  !Group::isGranted($this->getUser(),'score admin') && !Group::isGranted($this->getUser(),'score update') ) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CssrMainBundle:Score')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Score entity.');
        }

        $editForm = $this->createForm(new ScoreType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Score entity.
     *
     * @Route("/{id}", name="score_update")
     * @Method("PUT")
     * @Template("CssrMainBundle:Score:edit.html.twig")
     */
    public function updateAction ( Request $request, $id ) {

        if (  !Group::isGranted($this->getUser(),'score admin') && !Group::isGranted($this->getUser(),'score update') ) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        $em = $this->getDoctrine()->getManager();

        $score = $em->getRepository('CssrMainBundle:Score')->find($id);

        if (!$score) {
            throw $this->createNotFoundException('Unable to find Score entity.');
        }

        //$deleteForm = $this->createDeleteForm($id);
        //$editForm = $this->createForm(new ScoreType(), $score);
        //$editForm->submit($request);

        $value = $request->request->get('value');

        $isValid = true;
        if ( !empty($value) ) {
            //$value = $_POST['value'];
            if ( $value == 'N/A' ) {
                $value = null;
                $score->setValue($value);
            } else if ( in_array($value,array(1,2,3,4,5)) ) {
                $value = (int) $value;
                $score->setValue($value);
            } else {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        if ( $isValid ) {

            $score->setUpdatedBy($this->getUser());

            $em->flush();

            if ( $request->isXmlHttpRequest() ) {
                $api_response = new \stdClass();
                $api_response->status = 'success';

                // create a JSON-response with a 200 status code
                $response = new Response(json_encode($api_response));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        }

        if ( $request->isXmlHttpRequest() ) {
            $api_response = new \stdClass();
            $api_response->status = 'failed';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return array(
            'entity'      => $score,
            //'edit_form'   => $editForm->createView(),
            //'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Score entity.
     *
     * @Route("/{id}", name="score_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        if (  !Group::isGranted($this->getUser(),'score admin') && !Group::isGranted($this->getUser(),'score update') ) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        $em = $this->getDoctrine()->getManager();
        $score = $em->getRepository('CssrMainBundle:Score')->find($id);

        if ( $score ) {
            $em->remove($score);
            $em->flush();
        }

        if ($request->isXmlHttpRequest()) {
            $api_response = new \stdClass();
            $api_response->status = 'success';

            // create a JSON-response with a 200 status code
            $response = new Response(json_encode($api_response));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $this->redirect($this->generateUrl('score'));
    }

    /**
     * Creates a form to delete a Score entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
