<?php
namespace Cssr\MainBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpOldCommand extends ContainerAwareCommand
{
    public $DB_Old, $DB_new;
    protected $output;

    protected function configure()
    {
        $this
            ->setName('cssr:dump:old')
            ->setDescription('Dumps data from old database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->output->writeln('Starting Dump...');

        // create db connections
        try {
            $this->DB_Old = new \PDO("sqlsrv:Server=10.2.1.14,1433;Database=Adams_CSSR", "cssr", "tranuser13!");
            $this->DB_New = new \PDO('mysql:host=localhost;', 'root', 'password', array( \PDO::ATTR_PERSISTENT => false));
        } catch ( \Exception $e ) {
            $this->output->writeln($e.getMessage());
        }

        $tables = array(
            'sertblarea' => array(
                'keyAreaID',
                'varAreaName',
                'intDepartmentID',
                'intAreaOrder',
                'boolActive',
                'dtDateUpdated',
                'dtDateCreated'
            ),
            'sertblcenter' => array (
                'keyCenterID',
                'varCenterName',
                'varAddress1',
                'varAddress2',
                'varCity',
                'varState',
                'varZip',
                'varPhone',
                'varContact',
                'dtDateRemoved',
                'boolActive',
                'varUserUpdated',
                'dtDateUpdated',
                'varUserCreated',
                'dtDateCreated'
            ),
            'sertblcomment' => array (
                'keyCommentID',
                'varCommentTypeID',
                'varComment',
                'intUserID',
                'varArea',
                'boolActive',
                'dtDateRemoved',
                'dtWeekOf',
                'varFullNameUpdated',
                'varUserUpdated',
                'dtDateUpdated',
                'varUserCreated',
                'dtDateCreated'
            ),
            'sertbldorm' => array(
                'keyDormID',
                'varDormName',
                'intCenterID',
                'dtDateRemoved',
                'boolActive',
                'varUserUpdated',
                'dtDateUpdated',
                'varUserCreated',
                'dtDateCreated'
            ),
            'sertblmessages' => array(
                'keyMessageID',
                'varMessageTitle',
                'varMessage',
                'intCenterID',
                'varAudience',
                'boolActive',
                'varUserDeleted',
                'dtDateDeleted',
                'varUserUpdated',
                'dtDateUpdated',
                'varUserCreated',
                'dtDateCreated'
            ),
            'sertblscores' => array(
                'keyScoreID',
                'intUserID',
                'CPP',
                'CPP1',
                'Math',
                'Rdng',
                'G1',
                'G2',
                'G3',
                'G4',
                'G5',
                'G6',
                'Aux',
                'Aux2',
                'CT',
                'LS',
                'Res',
                'CSIO',
                'Couns',
                'HS',
                'TOTAL',
                'UNITS',
                'FINAL',
                'APPROVAL',
                'boolApproved',
                'boolActive',
                'dtWeekOf',
                'varUserUpdated',
                'dtDateUpdated',
                'varUserCreated',
                'dtDateCreated'
            ),
            'sertblskillset' => array(
                'keyCommentTypeID',
                'varCommentTypeName',
                'boolActive',
                'dtDateUpdated',
                'dtDateCreated'
            ),
            'sertblstaffstudents' => array(
                'keyStaffStudentID',
                'intStudentID',
                'intStaffID',
                'intAreaID',
                'intCenterID',
                'dtDateRemoved',
                'boolActive',
                'dtDateUpdated',
                'dtDateCreated'
            ),
            'sertbltitle' => array(
                'keyTitleID',
                'varTitleName',
                'boolActive',
                'dtDateUpdated',
                'dtDateCreated'
            ),
            'sertblusers' => array(
                'keyUserID',
                'varUserName',
                'varPassword',
                'varID',
                'varFirstName',
                'varMiddleName',
                'varLastName',
                'intCenterID',
                'intTitleID',
                'intDepartmentID',
                'intAreaID',
                'varArea',
                'dtDateOfEntry',
                'varPhone',
                'varEmail',
                'intDormID',
                'dtLastLoggedOn',
                'boolApproved',
                'boolActive',
                'dtDateRemoved',
                'varUserUpdated',
                'dtDateUpdated',
                'varUserCreated',
                'dtDateCreated'
            ),
            'sertblvocation' => array(
                'keyVocationID',
                'varVocationName',
                'intCenterID',
                'boolActive',
                'dtDateRemoved',
                'varUserUpdated',
                'dtDateUpdated',
                'varUserCreated',
                'dtDateCreated'
            )
        );

        foreach ( $tables as $name => $columns ) {
            $this->output->writeln('Dumping '.$name.'...');
            $mysqlDB = $this->DB_New;
            $sql = 'SELECT * FROM '.$name;
            $result = $this->DB_Old->query($sql,\PDO::FETCH_ASSOC);

            $fp = fopen("E:\\db_table_dump\\".$name.".sql",'w+b');

            $insert = 'INSERT INTO '.$name.' ('.implode(',',$columns).') VALUES ';
            foreach ( $result as $record ) {
                array_walk($record,function(&$value,$key) use ($mysqlDB){

                    if ( substr($key,0,3) === 'key' || substr($key,0,3) === 'int' ) {
                        if ( $value !== 0 && empty($value) ) {
                            $value = 'null';
                        }
                    } else if ( substr($key,0,3) === 'var' ) {
                        $value = $mysqlDB->quote($value);
                    } else if ( substr($key,0,4) === 'bool' ) {
                        if ( $value !== 0 && empty($value) ) {
                            $value = 'null';
                        }
                    } else if ( substr($key,0,2) === 'dt' ) {
                        if ( empty($value) ) {
                            $value = 'null';
                        } else {
                            $value = $mysqlDB->quote($value);
                        }
                    } else {
                        if ( $value !== 0 && empty($value) ) {
                            $value = 'null';
                        }
                    }

                });
                fwrite($fp, $insert.'('.implode(',',$record).');'."\n");
            }

            fclose($fp);
        }

        $this->output->writeln('Dump Complete');


    }

}