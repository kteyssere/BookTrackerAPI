<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Genre;
use App\Entity\ListBook;
use App\Entity\Persona;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Syfmony\Component\PasswordHasher\Hasher\UserPasswordInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
   /**
     * Hasher de mot de passe
     *
     * @var UserPasswordHasherInterface
     */
    private $userPasswordHasher;

    /**
     * @var Generator
     */
    private Generator $faker;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher){
        $paramName = "userPasswordHasher";
        $this->faker = Factory::create('fr_FR');
        $this->$paramName = $userPasswordHasher;
    }

    /**
     * Load New datas
     * 
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {

        $personas = [];
        for ($i=0; $i < 10; $i++) { 
        $gender = random_int( 0, 1);
    $genderStr = $gender ? 'male' : "female";
    $persona = new Persona();
    $birthdateStart =  new \DateTime("01/01/1980");
    $birthdateEnd = new \DateTime("01/01/2000");
    $birthDate = $this->faker->dateTimeBetween($birthdateStart,$birthdateEnd);
       $created = $this->faker->dateTimeBetween("-1 week", "now");
        $updated = $this->faker->dateTimeBetween($created, "now");
    $persona
    ->setPhone($this->faker->e164PhoneNumber())
    ->setGender($gender)
    ->setName($this->faker->lastName($genderStr))
    ->setSurname($this->faker->firstName($genderStr))
    ->setEmail($this->faker->email())
    ->setBirthdate( $birthDate)
    ->setAnonymous(false)
    ->setStatus("on")
    ->setCreatedAt($created)
    ->setUpdatedAt($updated);

    $manager->persist($persona);
    $personas[] = $persona;
    }

    $users = [];

    //Set Public User
    $publicUser = new User();
    $publicUser->setUsername("public");
    $publicUser->setRoles(["PUBLIC"]);
    $publicUser->setPassword($this->userPasswordHasher->hashPassword($publicUser, "public"));
    $publicUser->setPersona($personas[array_rand($personas, 1)]);
    $manager->persist($publicUser);
    $users[] = $publicUser;


    for ($i = 0; $i < 5; $i++) {
        $userUser = new User();
        $password = $this->faker->password(2, 6);
        $userUser->setUsername($this->faker->userName() . "@". $password);
        $userUser->setRoles(["USER"]);
        $userUser->setPassword($this->userPasswordHasher->hashPassword($userUser, $password));
        $userUser->setPersona($personas[array_rand($personas, 1)]);
        
        $manager->persist($userUser);
        $users[] = $userUser;
    }
    
        // Admins
    $adminUser = new User();
    $adminUser->setUsername("admin");
    $adminUser->setRoles(["ADMIN"]);
    $adminUser->setPassword($this->userPasswordHasher->hashPassword($adminUser, "password"));
    $adminUser->setPersona($personas[array_rand($personas, 1)]);
    $manager->persist($adminUser);
    $users[] = $adminUser;


       
        $bookTb  = [];

        // $product = new Product();
        // $manager->persist($product);
        for ($i=0; $i < 100; $i++) { 
            $db = $this->faker->dateTimeBetween("-1 week", "now");
            $da = $this->faker->dateTimeBetween($db, "now");
            
            $genre = new Genre();
            $genre->setName($this->faker->word())
            ->setCreatedAt($db)
            ->setUpdatedAt($da);
            if($i > 90){
                $genre->setStatus("off");
            }else{
                $genre->setStatus("on");
            }

            $book = new Book();
            $book->setName($this->faker->sentence(3))
            ->setTotalPages($this->faker->randomNumber(3, true))
            ->setPublisher($this->faker->word())
            ->setSynopsis($this->faker->text())
            ->setGenre($genre)
            ->setVolume(1)
            ->setCreatedAt($db)
            ->setUpdatedAt($da)
            ->setPublishingDate($this->faker->dateTime());
            if($i > 90){
                $book->setStatus("off");
            }else{
                $book->setStatus("on");
            }

            $bookTb[] = $book;

            $author = new Author();
            $author->setName($this->faker->name())
            ->setBiography($this->faker->text())
            ->addBook($book)
            ->setCreatedAt($db)
            ->setUpdatedAt($da);
            if($i > 90){
                $author->setStatus("off");
            }else{
                $author->setStatus("on");
            }

            $genre->addBook($book);

            $listBook = new ListBook();
            $listBook->setName($this->faker->word())
            ->addBook($book)
            ->setCreatedAt($db)
            ->setUpdatedAt($da);

            if($i > 90){
                $listBook->setStatus("off");
            }else{
                $listBook->setStatus("on");
            }

            $manager->persist($genre);
            $manager->persist($author);
            $manager->persist($book);
            $manager->persist($listBook);

        }

        // foreach ($bookTb as $key => $book) {
        //     $lists = $bookTb(array_rand($bookTb, 1));
        //     if($book->getId() !== $lists->getId()){

        //     }
        // }


        
        $manager->flush();
    }
}
