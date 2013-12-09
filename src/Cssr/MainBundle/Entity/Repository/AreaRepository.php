<?php

namespace Cssr\MainBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class AreaRepository extends EntityRepository
{
    public function findAll()
    {
        return $this->findBy(array(), array('name' => 'ASC'));
    }
}