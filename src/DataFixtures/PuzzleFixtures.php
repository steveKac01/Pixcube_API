<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Puzzle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PuzzleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $category = (new Category())
            ->setName('Tutorial')
            ->setDescription('How to play')
            ->setSlug('tutorial');

        $manager->persist($category);

        $puzzle = (new Puzzle())
            ->setName('une radio')
            ->setDifficulty(1)
            ->setObjectGrid(json_decode(file_get_contents(__DIR__ . '/tutorial-grid.json'), true, flags: JSON_THROW_ON_ERROR,))
            ->setIsPublished(true)
            ->setCategory($category);

        $manager->persist($puzzle);
        $manager->flush();
    }
}
