<?php
require getcwd().'/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(getcwd().'/.env');
$kernel=new App\Kernel('dev',true); $kernel->boot();
$em=$kernel->getContainer()->get('doctrine')->getManager();
$connection=$em->getConnection(); $connection->beginTransaction();
try {
 $tag=bin2hex(random_bytes(6));
 $user=(new App\Entity\User())->setEmail('smoke-'.$tag.'@example.test')->setNom('Test')->setPrenom('SQL Server')->setPassword('not-a-login-hash')->setRoles(['ROLE_BENEVOLE']);
 $artist=$em->getRepository(App\Entity\Artiste::class)->findOneBy(['pseudonyme'=>'C215']) ?? (new App\Entity\Artiste())->setPseudonyme('C215')->setBio('Test transactionnel');
 $lieu=(new App\Entity\Lieu())->setNom('Test SQL Server')->setQuartier('Centre')->setLatitude('48.8566000')->setLongitude('2.3522000');
 $tech=(new App\Entity\Technique())->setNom('Test-'.$tag);
 $unused=(new App\Entity\Technique())->setNom('Unused-'.$tag);
 $work=(new App\Entity\Oeuvre())->setTitre('Test SQL Server')->setDescription('Vérification transactionnelle')->setDateCreation(new DateTimeImmutable('2024-02-01'))->setLieu($lieu)->addArtiste($artist)->addTechnique($tech);
 $covered=(new App\Entity\Oeuvre())->setTitre('Œuvre recouverte')->setDescription('Test')->setLieu($lieu)->addTechnique($tech);
 $work->setOeuvreRecouverte($covered);
 $photo=(new App\Entity\Photo())->setOeuvre($work)->setAuteur($user)->setNomFichier(str_repeat('b',48).'.png');
 foreach ([$user,$artist,$lieu,$tech,$unused,$covered,$work,$photo] as $e) $em->persist($e);
 $em->flush(); $id=$work->getId(); $em->clear();
 $loaded=$em->find(App\Entity\Oeuvre::class,$id);
 if ($loaded->getArtistes()->count() !== 1 || $loaded->getPhotos()->count() !== 1 || !$loaded->getOeuvreRecouverte()) throw new RuntimeException('Relations invalides');
 $results=(new App\Service\ExerciseQueries($em))->results();
 foreach ($results as $label=>$rows) { if (!$rows) throw new RuntimeException('Requête sans résultat'); echo $label, ' : OK', PHP_EOL; }
 $rows=array_values($results)[2]; if (!array_filter($rows,fn($row)=>$row['nom']===$unused->getNom() && (int)$row['nombre']===0)) throw new RuntimeException('Technique inutilisée absente');
 echo "Écriture, relecture et relations SQL Server : OK\n";
} finally { $connection->rollBack(); $kernel->shutdown(); echo "Transaction de test annulée, aucune donnée de démonstration conservée.\n"; }
