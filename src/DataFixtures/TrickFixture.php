<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Trick;
use App\Entity\Video;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class TrickFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Categories
        foreach ($this->generateCategories() as $category) {
            $this->addReference($category->getName(), $category);
            $manager->persist($category);
        }

        // Tricks
        foreach ($this->generateTricks() as $trick) {
            /** @var Trick $trick */

            foreach ($trick->getComments() as $comment) {
                $manager->persist($comment);
            }

            $manager->persist($trick);
        }

        $manager->flush();
    }

    /**
     * @return \Generator<Category> 
     */
    private function generateCategories(): \Generator
    {
        $categoriesNames = array_unique(
            array_column($this->getTricksData(), 'category')
        );

        foreach ($categoriesNames as $categoryName) {
            yield (new Category)->setName($categoryName);
        }
    }

    /**
     * @return \Generator<Trick>
     */
    private function generateTricks(): \Generator
    {
        $tricksData = $this->getTricksData();

        foreach ($tricksData as $trickData) {
            $trick = (new Trick)
                ->setName($trickData["name"])
                ->setSlug(strtolower($this->slugger->slug($trickData["name"])))
                ->setCategory($this->getReference($trickData["category"]))
                ->setDescription($trickData["description"]);

            $videos = $trickData["videos"] ?? [];

            foreach ($videos as $url) {
                $video = (new Video())->setUrl($url);
                $trick->addVideo($video);
            }

            $author = $this->getReference(
                UserFixture::getRandomUser()->getUserIdentifier()
            );

            $trick->setAuthor($author);

            foreach ($this->generateComments(rand(15, 25)) as $comment) {
                $trick->addComment($comment);
            }

            yield $trick;
        }
    }

    private function generateComments(int $number): \Generator
    {
        if ($number < 1) {
            throw new \LogicException("The number of comments must be greater than 0");
        }

        $commentCreatedAt = new \DateTimeImmutable();

        $index = 1;

        do {
            // Delay 1 second to have sequential comments
            $interval = new \DateInterval("PT1S");
            $commentCreatedAt = $commentCreatedAt->add($interval);

            $text = "Comment #{$index}" . PHP_EOL . "Lorem ipsum...";

            $comment = (new Comment())
                ->setCreatedAt($commentCreatedAt)
                ->setAuthor($this->getReference(
                    UserFixture::getRandomUser()->getUserIdentifier()
                ))
                ->setText($text);

            $index++;

            yield $comment;
        } while (--$number);
    }

    private function getTricksData(): array
    {
        return [
            [
                "name" => "Ollie",
                "description" => "A simple trick where the boarder pops the snowboard by digging into the tail and then up into the air.",
                "category" => "Air",
                "videos" => [
                    "https://www.youtube.com/watch?v=D5sEJRx6QUY",
                    "https://www.dailymotion.com/video/xggf2a",
                    "https://vimeo.com/11823266",
                ],
            ],
            [
                "name" => "Frontside Grab",
                "description" => "One of the most essential snowboard tricks in any pro snowboarder’s arsenal, the frontside grab entails grabbing the toe edge with the back hand.",
                "category" => "Grab",
            ],
            [
                "name" => "Japan Air",
                "description" => "A grab of the toe edge using the front hand. The hand goes in between the feet to perform the move and the lead knee is also drawn to the snowboard.",
                "category" => "Grab",
            ],
            [
                "name" => "Backflip",
                "description" => "The rider performs a backflip after gaining air over a jump.",
                "category" => "Flip",
            ],
            [
                "name" => "180 spin",
                "description" => "The rider spins the board 180 degrees in the air.",
                "category" => "Spin",
            ],
            [
                "name" => "360 spin",
                "description" => "The rider spins the board for a full rotation in the air.",
                "category" => "Spin",
            ],
            [
                "name" => "50-50",
                "description" => "The fifty fifty is the most fundamental box or trail trick you can do on your snowboard. It will help make you comfortable on boxes or rails before you begin to do boardslides and other rail tricks. Before attempting a 50-50, be comfortable riding and carving in general. No other freestyle skills or tricks are really necessary to learn this. You will begin on a ride-on feature, where you don’t have to ollie, or jump, to get onto the feaure. Once you begin doing 50-50s onto urban-on features (features where you do have to ollie onto), you will need to know how to ollie off the lip as well.",
                "category" => "Slide",
            ],
            [
                "name" => "Backside 180 rewind",
                "description" => "The Rewind was the freshest move back in 2017, and it’s pretty much the hardest trick variation you can do now. Basically it’s when you almost complete a full rotation – 360, 540, 720, whatever – then at the last minute reverse spin direction and ‘rewind’ 180 degrees. In this case, Marcus is sending a huge, inverted backside three, but stalls, pokes and brings it back to 180. This is the easiest rotation done in the hardest possible way.",
                "category" => "Spin",
            ],
            [
                "name" => "Elbow carve",
                "description" => "The elbow carve is a direct descendent of the Euro carve, a stylised heel or toe side carve popularised by European hard booter Serge Vitelli back in the late 1980s. Today, style meisters like Tyler Chorlton are taking things to the next level of creativity and the variations seem to be endless. All you need to get your elbow carve going is a relatively smooth stretch of slope, edge control and a decent set of core muscles – what are you waiting for?",
                "category" => "Grab",
            ],
            [
                "name" => "Frontside Boardslide Backside Grab",
                "description" => "This trick is something you’ve probably never thought of, but now you’ve seen it you’ll wish you could do it too. It’s probably not even that hard, if you do it on a long, widefunbox in the park. But Rene Rinnekangas prefers to do it on a legit rail, with one of the sketchiest in-runs ever.",
                "category" => "Grab",
            ],
            [
                "name" => "540° spin rewind",
                "description" => "Spin the board twice and spin it backwards half a turn.",
                "category" => "Spin",
            ],
            [
                "name" => "Japan 540° spin rewind (日本語)",
                "description" => "Japanese touch to the 540° rewind.",
                "category" => "Spin",
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }
}
