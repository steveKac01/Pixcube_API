<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Puzzle;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // The fixtures run at every container start: do nothing if the tutorial is already there
        if (null !== $manager->getRepository(Category::class)->findOneBy(['slug' => 'tutorial'])) {
            return;
        }

        $categoryData = [
            ['slug' => 'tutorial', 'description' => 'How to play'],
            ['slug' => 'fruits', 'description' => 'Three per day'],
            ['slug' => 'electronic', 'description' => 'Description test'],
        ];

        $puzzleData = [
            ['name' => 'une radio', 'difficulty' => 1, 'fileName' => 'tutorial-grid.json'],
            ['name' => 'une pomme', 'difficulty' => 5, 'fileName' => 'puzzle1-grid.json'],
            ['name' => 'un casque audio', 'difficulty' => 8, 'fileName' => 'puzzle2-grid.json'],
        ];


        foreach ($categoryData as $i => $categoryItem) {
            $category = $this->createCategory(
                $categoryItem['slug'],
                $categoryItem['description']
            );

            $puzzleItem = $puzzleData[$i];

            $puzzle = $this->createPuzzle(
                $puzzleItem['name'],
                $puzzleItem['difficulty'],
                $puzzleItem['fileName'],
                $category
            );

            $manager->persist($category);
            $manager->persist($puzzle);
        }

        $user = (new User())
            ->setEmail('test@email.fr')
            ->setPassword('Pass1234!');

        $manager->persist($user);

        $manager->flush();
    }

    private function createCategory(string $slug, string $description): Category
    {
        return (new Category())
            ->setName(ucfirst($slug))
            ->setDescription($description)
            ->setSlug($slug);
    }

    private function createPuzzle(string $name, int $difficulty, string $fileName, Category $category): Puzzle
    {
        return (new Puzzle())
            ->setName($name)
            ->setDifficulty($difficulty)
            ->setObjectGrid($this->load_grid($fileName))
            ->setIsPublished(true)
            ->setCategory($category);
    }

    private function load_grid(string $fileName): array
    {
        return json_decode(file_get_contents(__DIR__ . '/' . $fileName), true, flags: JSON_THROW_ON_ERROR,);
    }
}
