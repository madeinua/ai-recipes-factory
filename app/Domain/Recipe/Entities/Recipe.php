<?php

namespace App\Domain\Recipe\Entities;

use App\Domain\Recipe\ValueObjects\Ingredient;

final readonly class Recipe
{
    /**
     * @param string $id
     * @param string $title
     * @param string|null $excerpt
     * @param array $instructions
     * @param int $numberOfPersons
     * @param int $timeToCook
     * @param int $timeToPrepare
     * @param array $ingredients
     * @param \DateTimeImmutable $createdAt
     * @param \DateTimeImmutable $updatedAt
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $excerpt,
        public array $instructions,
        public int $numberOfPersons,
        public int $timeToCook,
        public int $timeToPrepare,
        public array $ingredients,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
        $this->assertValid();
    }

    private function assertValid()
    {
        if ($this->title === '' || $this->numberOfPersons < 1 || $this->timeToCook < 0 || $this->timeToPrepare < 0) {
            throw new \InvalidArgumentException('Invalid recipe primitives.');
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'excerpt'         => $this->excerpt,
            'instructions'    => $this->instructions,
            'numberOfPersons' => $this->numberOfPersons,
            'timeToCook'      => $this->timeToCook,
            'timeToPrepare'   => $this->timeToPrepare,
            'ingredients'     => array_map(static fn(Ingredient $i) => $i->toArray(), $this->ingredients),
            'createdAt'       => $this->createdAt->format(DATE_ATOM),
            'updatedAt'       => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
