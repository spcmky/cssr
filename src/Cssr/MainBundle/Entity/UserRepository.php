<?php

namespace Cssr\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findAllStaff()
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder('u')
            ->innerJoin('u.groups', 'g', 'WITH', 'g.name = :groupName')
            ->setParameter('groupName', 'Admin');

        $query = $qb->getQuery();


        $em->createQuery('
            SELECT u
            FROM CssrMainBundle:Group g
            JOIN u.group g
            WHERE p.id = :id'
        )->setParameter('id', $id);


        return $query->getResult();
    }

}