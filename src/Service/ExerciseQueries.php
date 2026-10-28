<?php
namespace App\Service;
use App\Entity\{Lieu,Oeuvre,Technique,User};
use Doctrine\ORM\EntityManagerInterface;
final class ExerciseQueries {
    public function __construct(private EntityManagerInterface $em) {}
    public function results(): array {
        return [
            'Lieux par quartier'=>$this->em->createQueryBuilder()->select('l.adresse, l.quartier')->from(Lieu::class,'l')->orderBy('l.quartier','ASC')->getQuery()->getArrayResult(),
            'Œuvres après le 01/01/2023'=>$this->em->createQueryBuilder()->select('o.titre, o.dateCreation, a.pseudonyme')->from(Oeuvre::class,'o')->leftJoin('o.artistes','a')->where('o.dateCreation > :date')->setParameter('date',new \DateTimeImmutable('2023-01-01'))->getQuery()->getArrayResult(),
            'Nombre d’œuvres par technique'=>$this->em->createQueryBuilder()->select('t.nom, COUNT(o.id) AS nombre')->from(Technique::class,'t')->leftJoin('t.oeuvres','o')->groupBy('t.id, t.nom')->getQuery()->getArrayResult(),
            'Photographes de C215 dans le quartier Centre'=>$this->em->createQueryBuilder()->select('DISTINCT u.nom, u.prenom, u.email')->from(User::class,'u')->join('u.photos','p')->join('p.oeuvre','o')->join('o.artistes','a')->join('o.lieu','l')->where('a.pseudonyme = :artiste AND l.quartier = :quartier')->setParameter('artiste','C215')->setParameter('quartier','Centre')->getQuery()->getArrayResult(),
        ];
    }
}
