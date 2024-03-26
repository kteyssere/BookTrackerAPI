<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Conversation;
use App\Entity\ListBook;
use App\Entity\Message;
use App\Entity\Persona;
use App\Entity\Progression;
use App\Entity\Review;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Google\Client as GC;
use Google\Service\Books as GSB;

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

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
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
        $client = new GC();
        $client->setApplicationName("Client_Library_Examples");
        $client->setDeveloperKey("YOUR_APP_KEY");

        $service = new GSB([$client]);
        $results = [];

        $query = 'subject:fiction';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];
        
        $resultsFiction = $service->volumes->listVolumes($query, $optParams)["items"];

        $query = 'subject:mystery';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];

        $resultsMystery = $service->volumes->listVolumes($query, $optParams)["items"];


        $query = 'subject:science%20fiction';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];
        
        $resultsSf = $service->volumes->listVolumes($query, $optParams)["items"];

        $query = 'subject:fantasy';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];
        
        $resultsFantasy = $service->volumes->listVolumes($query, $optParams)["items"];

        $query = 'subject:horror';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];
        
        $resultsHorror = $service->volumes->listVolumes($query, $optParams)["items"];

        $query = 'subject:thriller';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 15,
            'langRestrict'=>'fr'
        ];
        
        $resultsThriller = $service->volumes->listVolumes($query, $optParams)["items"];

        $query = 'subject:romance';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 15,
            'langRestrict'=>'fr'
        ];
        
        $resultsRomance = $service->volumes->listVolumes($query, $optParams)["items"];

        $query = 'subject:history';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];
        
        $resultsHistory = $service->volumes->listVolumes($query, $optParams)["items"];

        $query = 'subject:biography';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];
        
        $resultsBiography = $service->volumes->listVolumes($query, $optParams)["items"];


        $query = 'subject:autobiography';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];
        
        $resultsAutobiography = $service->volumes->listVolumes($query, $optParams)["items"];

        $query = 'subject:poetry';
        $optParams = [
            'orderBy'=>'newest',
            'maxResults' => 10,
            'langRestrict'=>'fr'
        ];
        
        $resultsPoetry = $service->volumes->listVolumes($query, $optParams)["items"];

        $results = array_merge($results, $resultsFiction, $resultsMystery, $resultsSf, 
        $resultsFantasy, $resultsHorror, $resultsThriller, $resultsRomance, $resultsHistory,
        $resultsBiography, $resultsAutobiography, $resultsPoetry);

        $unique_array = [];
        foreach($results as $element) {
            $hash = $element['volumeInfo']["title"];
            $unique_array[$hash] = $element;
        }
        $results = array_values($unique_array);

        $personas = [];
        for ($i = 0; $i < 10; $i++) {
            $gender = random_int(0, 1);
            $genderStr = $gender ? 'male' : "female";
            $persona = new Persona();
            $birthdateStart =  new \DateTime("01/01/1980");
            $birthdateEnd = new \DateTime("01/01/2000");
            $birthDate = $this->faker->dateTimeBetween($birthdateStart, $birthdateEnd);
            $created = $this->faker->dateTimeBetween("-1 week", "now");
            $updated = $this->faker->dateTimeBetween($created, "now");
            $persona
                ->setPhone($this->faker->e164PhoneNumber())
                ->setGender($gender)
                ->setName($this->faker->lastName($genderStr))
                ->setSurname($this->faker->firstName($genderStr))
                ->setEmail($this->faker->email())
                ->setBirthdate($birthDate)
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
            $userUser->setUsername($this->faker->userName() . "@" . $password);
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

        for($i = 0; $i < count($results) && strlen($results[$i]['volumeInfo']["title"]) < 255; $i++) {
            $db = $this->faker->dateTimeBetween("-1 week", "now");
            $da = $this->faker->dateTimeBetween($db, "now");
           
            $imgLinks = [];
            if($results[$i]['volumeInfo']["imageLinks"] !== null){
                $imgLinks[] = [$results[$i]['volumeInfo']["imageLinks"]["smallThumbnail"], $results[$i]['volumeInfo']["imageLinks"]["thumbnail"]];
            }

            $categories = $results[$i]['volumeInfo']["categories"] ?? [];
            $categoriesOfBook = [];
            for($j = 0; $j < count($categories); $j++){
               
                $allready = $manager->getRepository(Category::class)->findOneBy(["name"=> $categories[$j]]);
                
                if($allready === null){
                    $categ = new Category();
                    $categ->setName($categories[$j])
                    ->setCreatedAt($db)
                    ->setUpdatedAt($da)
                    ->setStatus("on");
                    $manager->persist($categ);
                    $manager->flush();

                    $categoriesOfBook[] = $categ;
                }else{
                    $categoriesOfBook[] = $allready;
                }
            }

            $authors = $results[$i]['volumeInfo']["authors"] ?? [];
            $authorsOfBook = [];
            for($j = 0; $j < count($authors); $j++){
                $allready = $manager->getRepository(Author::class)->findOneBy(["name"=> $authors[$j]]);
                if($allready === null){
                    $authr = new Author();
                    $authr->setName($authors[$j])
                    ->setCreatedAt($db)
                    ->setUpdatedAt($da)
                    ->setStatus("on");
                    $manager->persist($authr);
                    $manager->flush();

                    $authorsOfBook[] = $authr;
                }else{
                    $authorsOfBook[] = $allready;
                }
            }

            $book = new Book();
            $book->setTitle($results[$i]['volumeInfo']["title"] ?? " ")
                ->setTotalPages($results[$i]['volumeInfo']["pageCount"] ?? 0)
                ->setPublisher($results[$i]['volumeInfo']["publisher"] ?? " ")
                ->setDescription($results[$i]['volumeInfo']["description"] ?? " ")
                ->addCategories($categoriesOfBook)
                ->setIsbn10($results[$i]['volumeInfo']["industryIdentifiers"][1]["identifier"] ??" ")
                ->setIsbn13($results[$i]['volumeInfo']["industryIdentifiers"][0]["identifier"] ??" ")
                ->addAuthors($authorsOfBook)
                ->setImageLinks($imgLinks)
                ->setCreatedAt($db)
                ->setUpdatedAt($da)
                ->setStatus("on")
                ->setPublishingDate(new DateTime($results[$i]['volumeInfo']["publishedDate"]));

                $listBook = new ListBook();
                $listBook->setName($this->faker->word())
                    ->addBook($book)
                    ->setPersona($personas[array_rand($personas, 1)])
                    ->setCreatedAt($db)
                    ->setUpdatedAt($da);
    
    
                if ($i > 90) {
                    $listBook->setStatus("off");
                } else {
                    $listBook->setStatus("on");
                }
    
                if($i < 10){
                    $review = new Review();
                $review->setTitle($this->faker->word())
                    ->setBook($book)
                    ->setUser($personas[$i])
                    ->setComment($this->faker->text())
                    ->setLikes($i)
                    ->setStatus("on")
                    ->setCreatedAt($db)
                    ->setUpdatedAt($da);
                    
                }
            $bookTb[] = $book;
            $manager->persist($book);
            $manager->persist($listBook);
            $manager->persist($review);

        }

        for ($i = 0; $i < 50; $i++) {
            $bookForPrg = $bookTb[array_rand($bookTb, 1)];
            $progression = new Progression();
            $progression
            ->setBook($bookForPrg)
            ->setProgress(rand(1,$bookForPrg->getTotalPages()) ?? 0)
            ->setPersona($personas[array_rand($personas, 1)])
            ->setStatus("on")
            ->setCreatedAt($db)
            ->setUpdatedAt($da);

            $manager->persist($progression);

        }

        for ($i = 0; $i < 5; $i++) {
            $persona1 = $personas[array_rand($personas, 1)];
            $persona2 = $personas[array_rand($personas, 1)];
            $conversation = new Conversation();
            $conversation->addParticipant($persona1)
            ->addParticipant($persona2)
            ->setStatus("on")
            ->setCreatedAt($db)
            ->setUpdatedAt($da);
            $manager->persist($conversation);

            for($j = 0; $j < 4; $j++){
                $message = new Message();
                $message->setContent($this->faker->text())
                ->setAuthor($persona1)
                ->setConversation($conversation)
                ->setStatus("on")
                ->setCreatedAt($db)
                ->setUpdatedAt($da);
                $manager->persist($message);

            }

            for($j = 0; $j < 4; $j++){
                $message = new Message();
                $message->setContent($this->faker->text())
                ->setAuthor($persona2)
                ->setConversation($conversation)
                ->setStatus("on")
                ->setCreatedAt($db)
                ->setUpdatedAt($da);
                $manager->persist($message);
            }
        }
        $manager->flush();
    }
}
