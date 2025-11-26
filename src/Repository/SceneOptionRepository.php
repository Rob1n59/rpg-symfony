<?php

namespace App\Repository;

use App\Entity\SceneOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SceneOption>
 *
 * @method SceneOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method SceneOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method SceneOption[]    findAll()
 * @method SceneOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SceneOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SceneOption::class);
    }

//    /**
//     * @return SceneOption[] Returns an array of SceneOption objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SceneOption
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}