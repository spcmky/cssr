<?php
namespace Cssr\MainBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends ContainerAwareCommand
{
    protected $DB_Old;
    protected $DB_New;

    protected function configure()
    {
        $this
            ->setName('cssr:import')
            ->setDescription('Imports data from old database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->output->writeln('Starting Import...');

        // create db connections
        try {
            $this->DB_Old = new \PDO('mysql:host=localhost;dbname=cssr_original', 'root', '', array( \PDO::ATTR_PERSISTENT => false));
            $this->DB_New = new \PDO('mysql:host=localhost;dbname=cssr', 'root', '', array( \PDO::ATTR_PERSISTENT => false));
        } catch ( \Exception $e ) {
            $this->output->writeln($e.getMessage());
        }

        //$this->createCenters();
        //$this->createDorms();
        //$this->createVocations();
        //$this->createAreas();
        //$this->createUsers();

        //$this->createCourses();

        //$this->addUsersToGroups();

        //$this->addCoursesToStudents();

        $this->addStudentScores();

    }

    protected function addStudentScores() {
        $this->output->writeln('Adding student scores...');

        $em = $this->getContainer()->get('doctrine')->getManager();

        $areas = $em->getRepository('CssrMainBundle:Area')->findAll();

        $sql = "SELECT U.* FROM cssr_user_group UG LEFT JOIN cssr_user U ON U.id = UG.user_id WHERE UG.group_id = :groupId";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->bindValue('groupId', 6);
        $stmt->execute();
        $students = $stmt->fetchAll();

        foreach ( $students as $student ) {

            $scores = $this->DB_Old->query("SELECT * FROM sertblscores WHERE intUserID = ".$student['id'],\PDO::FETCH_OBJ);

            $sql = "
            SELECT C.id, A.id area_id, A.name area_name, U.id user_id, U.firstname user_firstname, U.lastname user_lastname
            FROM cssr_student_course UC
            LEFT JOIN cssr_course C ON C.id = UC.course_id
            LEFT JOIN cssr_area A ON A.id = C.area_id
            LEFT JOIN cssr_user U ON U.id = C.user_id
            WHERE UC.student_id = :userId";

            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('userId', $student['id']);
            $stmt->execute();
            $courses = $stmt->fetchAll();

            foreach ( $scores as $score ) {

                //$this->output->writeln(print_r($score,true));

                // find the course id
                foreach ( $areas as $area ) {
                    $area_name = $area->getName();

                    if ( $score->$area_name ) {



                        foreach ( $courses as $course ) {

                            if ( $course['area_id'] == $area->getId() ) {

                                //$this->output->writeln($score->$area_name);

                                $sql  = "INSERT INTO cssr_score(course_id,student_id,value,period)";
                                $sql .= "VALUES(".$course['id'].",".$student['id'].",".$score->$area_name.",'".$score->dtWeekOf."')";
                                $this->DB_New->query($sql);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function addCoursesToStudents() {
        $this->output->writeln('Adding courses to students...');

        $staff = $this->DB_Old->query("SELECT intStudentID, intStaffID, intAreaID FROM sertblstaffstudents WHERE intStaffID > 0",\PDO::FETCH_OBJ);

        foreach ( $staff as $s ) {

            try {

                $sql = "SELECT id FROM cssr_course WHERE user_id = ".$s->intStaffID." AND area_id = ".$s->intAreaID." ";
                $course_id = $this->DB_New->query($sql)->fetchColumn();

                $sql = 'INSERT INTO cssr_student_course (course_id,student_id)
                    VALUES ('.$course_id.','.$s->intStudentID.')';
                $this->DB_New->query($sql);
            } catch ( \Exception $e ) {
                $this->output->writeln($e->getMessage());
            }
        }

    }

    protected function createCourses () {

        $this->output->writeln('Creating Courses...');

        $staff = $this->DB_Old->query("SELECT keyUserID, intAreaID FROM sertblusers WHERE intAreaID > 0",\PDO::FETCH_OBJ);

        foreach ( $staff as $s ) {
            try {
                $sql = 'INSERT INTO cssr_course (user_id,area_id)
                    VALUES ('.$s->keyUserID.','.$s->intAreaID.')';

                $this->DB_New->query($sql);
            } catch ( \Exception $e ) {
                $this->output->writeln($e->getMessage());
            }
        }

    }

    protected function addUsersToGroups () {

        $this->output->writeln('Adding users to groups...');

        $users = $this->DB_Old->query("SELECT keyUserID, intTitleID FROM sertblusers WHERE intTitleID > 0",\PDO::FETCH_OBJ);

        foreach ( $users as $user ) {
            try {
                $sql = 'INSERT INTO cssr_user_group (user_id, group_id) VALUES ('.$user->keyUserID.', '.$user->intTitleID.')';

                $this->DB_New->query($sql);
            } catch ( \Exception $e ) {
                $this->output->writeln($e->getMessage());
            }
        }
    }


    protected function createCenters () {

        $this->output->writeln('Creating Centers...');

        $result = $this->DB_Old->query("SELECT * FROM sertblcenter",\PDO::FETCH_OBJ);

        foreach ( $result as $center ) {

        //                [keyCenterID] => 51
        //                [varCenterName] => Delaware Valley
        //                [varAddress1] => 9368 New York 97
        //                [varAddress2] =>
        //                [varCity] => Callicoon
        //                [varState] => NY
        //                [varZip] => 12723
        //                [varPhone] => 8458875400
        //                [varContact] =>
        //                [dtDateRemoved] =>
        //                [boolActive] => 1
        //                [varUserUpdated] => sblackwell
        //                [dtDateUpdated] => 2012-07-03 11:17:39
        //                [varUserCreated] => sblackwell
        //                [dtDateCreated] => 2012-07-03 11:17:39

            $address = $center->varAddress1;
            if ( trim($center->varAddress2) != '' ) {
                $address .= chr(10).$center->varAddress2;
            }

            $sql = 'INSERT INTO cssr_center (id,name,address,city,state,postcode,phone,created,updated,created_by,updated_by,active)
                    VALUES ('.$center->keyCenterID.',
                            \''.$center->varCenterName.'\',
                            \''.$address.'\',
                            \''.$center->varCity.'\',
                            \''.$center->varState.'\',
                            \''.$center->varZip.'\',
                            \''.$center->varPhone.'\',
                            \''.$center->dtDateCreated.'\',
                            \''.$center->dtDateUpdated.'\',
                            1,
                            1,
                            1
                    )';

            $this->DB_New->query($sql);

        }

    }

    protected function createAreas () {

        $this->output->writeln('Creating Areas...');

        $result = $this->DB_Old->query("SELECT * FROM sertblarea",\PDO::FETCH_OBJ);

        foreach ( $result as $area ) {
            //$this->output->writeln(print_r($area,true));

            //            [keyAreaID] => 18
            //            [varAreaName] => CPP1
            //            [intDepartmentID] =>
            //            [intAreaOrder] => 2
            //            [boolActive] => 1
            //            [dtDateUpdated] =>
            //            [dtDateCreated] =>


            $sql = 'INSERT INTO cssr_area (id,name)
                    VALUES ('.$area->keyAreaID.',
                            \''.$area->varAreaName.'\'
                    )';
            //$this->output->writeln($sql);
            $this->DB_New->query($sql);
        }
    }

    protected function createVocations () {

        $this->output->writeln('Creating Vocations...');

        $result = $this->DB_Old->query("SELECT * FROM sertblvocation",\PDO::FETCH_OBJ);

        foreach ( $result as $vocation ) {
            //$this->output->writeln(print_r($area,true));

            //            [keyVocationID] => 18
            //            [varVocationName] => CPP1
            //            [intCenterID] => 1
            //            [boolActive] => 1
            //            [dtDateUpdated] =>
            //            [dtDateCreated] =>


            $sql = 'INSERT INTO cssr_vocation (id,name,center_id)
                    VALUES ('.$vocation->keyVocationID.',
                            \''.$vocation->varVocationName.'\',
                            '.$vocation->intCenterID.'
                    )';
            //$this->output->writeln($sql);
            $this->DB_New->query($sql);
        }
    }

    protected function createDorms () {

        $this->output->writeln('Creating Dorms...');

        $result = $this->DB_Old->query("SELECT * FROM sertbldorm",\PDO::FETCH_OBJ);

        foreach ( $result as $dorm ) {
            //$this->output->writeln(print_r($area,true));

            //            [keyDormID] => 18
            //            [varDormName] => CPP1
            //            [intCenterID] =>
            //            [boolActive] => 1
            //            [dtDateUpdated] =>
            //            [dtDateCreated] =>

            $sql = 'INSERT INTO cssr_dorm (id,name,center_id)
                    VALUES ('.$dorm->keyDormID.',
                            \''.$dorm->varDormName.'\',
                            '.$dorm->intCenterID.'
                    )';
            //$this->output->writeln($sql);
            $this->DB_New->query($sql);
        }

    }

    protected function createUsers () {

        $this->output->writeln('Creating Users...');

        $result = $this->DB_Old->query("SELECT * FROM sertblusers",\PDO::FETCH_OBJ);

        $userManager = $this->getContainer()->get('fos_user.user_manager');

        $em = $this->getContainer()->get('doctrine')->getManager();

        $count = 0;
        foreach ( $result as $user ) {
            //$this->output->writeln(print_r($user,true));

            //    [keyUserID] => 48057
            //    [varUserName] => OareBr
            //    [varPassword] => happy
            //    [varID] => 1325772
            //    [varFirstName] => Brandon
            //    [varMiddleName] => W
            //    [varLastName] => Oare
            //    [intCenterID] => 48
            //    [intTitleID] => 6
            //    [intDepartmentID] => -1
            //    [intAreaID] => -1
            //    [varArea] =>
            //    [dtDateOfEntry] => 2013-01-30 00:00:00
            //    [varPhone] => 1234
            //    [varEmail] => 1234
            //    [intDormID] => 85
            //    [dtLastLoggedOn] =>
            //    [boolApproved] =>
            //    [boolActive] => 1
            //    [dtDateRemoved] =>
            //    [varUserUpdated] => Wolridgeba
            //    [dtDateUpdated] => 2013-07-17 18:30:09
            //    [varUserCreated] => Wolridgeba
            //    [dtDateCreated] => 2013-01-30 16:32:22

            //$found = $this->DB_New->query('SELECT 1 FROM cssr_user WHERE id = '.$user->keyUserID)->fetchColumn();

            if ( /*!$found*/ $user->keyUserID == 48705 ) {

                $new_user = $userManager->createUser();

                $new_user->setId($user->keyUserID);
                $new_user->setUsername($user->varUserName);
                $new_user->setEmail($user->varEmail);
                $new_user->setPlainPassword($user->varPassword);

                $new_user->setPhone($user->varPhone);
                $new_user->setFirstname($user->varFirstName);
                $new_user->setMiddlename($user->varMiddleName);
                $new_user->setLastname($user->varLastName);

                try {

                    if ( $user->intCenterID ) {
                        $found = $this->DB_New->query('SELECT 1 FROM cssr_center WHERE id = '.$user->intCenterID)->fetchColumn();
                        if ( $found ) {
                            $center = $em->getRepository('CssrMainBundle:Center')->find($user->intCenterID);
                            if ( $center ) {
                                $new_user->setCenter($center);
                            }
                        }
                    }

                    if ( $user->intDormID ) {
                        $found = $this->DB_New->query('SELECT 1 FROM cssr_dorm WHERE id = '.$user->intDormID)->fetchColumn();
                        if ( $found ) {
                            $dorm = $em->getRepository('CssrMainBundle:Dorm')->find($user->intDormID);
                            if ( $dorm ) {
                                $new_user->setDorm($dorm);
                            }
                        }
                    }

                    if ( $user->dtDateOfEntry ) {
                        $date = new \DateTime($user->dtDateOfEntry);
                        if ( (int)$date->format('Y') >= 2000 ) {
                            $new_user->setEntry($date);
                        }
                    }

                    $new_user->setEnabled(true);

                    $userManager->updateUser($new_user);
                } catch ( \Exception $e ) {
                    $this->output->writeln('Issues with User: '.$user->keyUserID);
                    $this->output->writeln($e->getMessage());
                }
            }

            $count++;
            /*
            $sql = 'INSERT INTO cssr_user (id,username,)
                    VALUES ('.$area->keyAreaID.',
                            \''.$area->varAreaName.'\'
                    )';
            */
            //$this->output->writeln($sql);
            //$this->DB_New->query($sql);
        }

    }
}