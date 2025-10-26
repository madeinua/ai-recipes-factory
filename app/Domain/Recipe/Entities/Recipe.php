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
    private function __construct(
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
    }

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
     * @return self
     */
    public static function create(
        string $id,
        string $title,
        ?string $excerpt,
        array $instructions,
        int $numberOfPersons,
        int $timeToCook,
        int $timeToPrepare,
        array $ingredients,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        if ($title === '' || $numberOfPersons < 1 || $timeToCook < 0 || $timeToPrepare < 0) {
            throw new \InvalidArgumentException('Invalid recipe primitives.');
        }

        return new self(
            $id, $title, $excerpt, array_values($instructions),
            $numberOfPersons, $timeToCook, $timeToPrepare,
            $ingredients, $createdAt, $updatedAt
        );
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
