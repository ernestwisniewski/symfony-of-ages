<?php

namespace App\Domain\Shared\ValueObject;

final readonly class Timestamp
{
    public function __construct(private \DateTimeImmutable $date)
    {
    }

    public static function now(): self
    {
        return new self(new \DateTimeImmutable());
    }

    public static function fromString(string $value): self
    {
        return new self(new \DateTimeImmutable($value));
    }

    public function format(string $format = DATE_ATOM): string
    {
        return $this->date->format($format);
    }

    public function get(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
