<?php

namespace App\Tests\Support;

use App\Entity\Category;
use App\Entity\Puzzle;
use Doctrine\ORM\EntityManagerInterface;

trait CreatePuzzle
{
    /**
     * Create and persist a puzzle.
     * By default the puzzle is published
     * If solution grid isn't provide, an empty array will be load by default.
     * 
     * @param string $categorySlug the slug from the category, 
     * @param array|null $solutionGrid
     * @param boolean $isPublished
     * @param EntityManagerInterface|null $em
     * @return Puzzle
     */
    private function createPuzzle(string $categorySlug, array $solutionGrid, ?EntityManagerInterface $em, bool $isPublished = true): Puzzle
    {

        if (!$em){
            $em = static::getContainer()->get(EntityManagerInterface::class);
        }

        $category = (new Category())->setName(\ucfirst($categorySlug))
            ->setSlug($categorySlug)
            ->setDescription('Puzzle test');

        $em->persist($category);

        $puzzle = (new Puzzle())->setCategory($category)
            ->setDifficulty(1)
            ->setIsPublished($isPublished)
            ->setName('Object name revealed only on victoy')
            ->setObjectGrid($solutionGrid);

        $em->persist($puzzle);
        $em->flush();
        return $puzzle;
    }
}
