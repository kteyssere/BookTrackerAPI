<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Genre;
use App\Entity\ListBook;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
class AppFixtures extends Fixture
{
    /**
     * @var Generator
     */
    private Generator $faker;

    public function __construct(){
        $this->faker = Factory::create('fr_FR');
    }

    /**
     * Load New datas
     * 
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
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
