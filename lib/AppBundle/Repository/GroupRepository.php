<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 09.12.2016
 * Time: 21:12
 */

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class GroupRepository extends EntityRepository
{
    /**
     * @return QueryBuilder
     */
    public function createAlphabeticalQueryBuilder()
    {
        return $this->createQueryBuilder("g")
            ->orderBy('g.name', 'ASC');
    }
}