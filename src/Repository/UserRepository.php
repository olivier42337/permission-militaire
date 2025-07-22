<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Récupère tous les utilisateurs ayant un rôle donné (ex : ROLE_MILITAIRE).
     *
     * @param string $role
     * @return User[]
     */
   public function findByRole(string $role): array
{
    return $this->createQueryBuilder('u')
        ->where('u.roles LIKE :role')
        ->setParameter('role', '%"'.$role.'"%')
        ->orderBy('u.nom', 'ASC')
        ->getQuery()
        ->getResult();
}

}
