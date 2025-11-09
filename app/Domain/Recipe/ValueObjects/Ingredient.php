<?php

namespace App\Domain\Recipe\ValueObjects;

final readonly class Ingredient
{
    /**
     * @param string $name
     * @param float $value
     * @param string $measure
     */
    public function __construct(
        public string $name,
        public float $value,
        public string $measure,
    ) {
        $this->assertValid();
    }

    /**
     * @throws \InvalidArgumentException
     * @return void
     */
    private function assertValid(): void
    {
        if ($this->name === '') {
            throw new \InvalidArgumentException('Ingredient name cannot be empty.');
        }

        if (mb_strlen($this->name) > 120) {
            throw new \InvalidArgumentException('Ingredient name too long.');
        }

        if (!is_finite($this->value)) {
            throw new \InvalidArgumentException('Ingredient value must be a finite number.');
        }

        if ($this->value < 0) {
            throw new \InvalidArgumentException('Ingredient value cannot be negative.');
        }

        if ($this->measure !== '' && mb_strlen($this->measure) > 50) {
            throw new \InvalidArgumentException('Ingredient measure too long');
        }
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            value: (float) ($data['value'] ?? 0),
            measure: (string) ($data['measure'] ?? ''),
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'    => $this->name,
            'value'   => $this->value,
            'measure' => $this->measure,
        ];
    }
}
