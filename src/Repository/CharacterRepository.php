<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Character|null find($id, $lockMode = null, $lockVersion = null)
 * @method Character|null findOneBy(array $criteria, array $orderBy = null)
 * @method Character[]    findAll()
 * @method Character[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    /**
     * @return Character[] Returns an array of Movie objects
     */

    public function getByName($name)
    {
        return $this->createQueryBuilder('character')
            ->andWhere('character.name = :name')
            ->setParameter('name', $name)
            ->getQuery()->getOneOrNullResult()
            ;
    }

    public function listByName($name)
    {
        return $this->createQueryBuilder('character')
            ->andWhere('character.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()->execute()
            ;
    }
    /*
    public function findOneBySomeField($value): ?Character
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
