<?php
namespace Cssr\MainBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PruneCommand extends ContainerAwareCommand
{
    protected function configure () {
        $this->setName('cssr:prune');
        $this->setDescription('Prunes data from database.');
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {

        $output->writeln('Starting Prune...');

        // setup db connection
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null); // turn off logger

        // remove comment standards
        $sql  = 'DELETE cssr_comment_standard ';
        $sql .= 'FROM cssr_comment_standard ';
        $sql .= 'INNER JOIN cssr_comment ON cssr_comment.id = cssr_comment_standard.comment_id ';
        $sql .= 'WHERE cssr_comment.created <= NOW() - INTERVAL 4 MONTH ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $output->writeln('Pruning '.$stmt->rowCount().' comment standards.');

        // remove comments
        $sql  = 'DELETE ';
        $sql .= 'FROM cssr_comment ';
        $sql .= 'WHERE cssr_comment.created <= NOW() - INTERVAL 4 MONTH ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $output->writeln('Pruning '.$stmt->rowCount().' comments.');


        // remove scores
        $sql  = 'DELETE ';
        $sql .= 'FROM cssr_score ';
        $sql .= 'WHERE cssr_score.created <= NOW() - INTERVAL 4 MONTH ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $output->writeln('Pruning '.$stmt->rowCount().' scores.');


        $output->writeln('Done.');
    }
}