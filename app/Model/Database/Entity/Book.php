<?php

namespace App\Model\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Model\Database\Repository\BookRepository")]
#[ORM\Table(name: "book")]
class Book extends BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\Column(type: "string", name: "name", nullable: true)]
    private ?string $name;

    #[ORM\Column(type: "string", name: "author", nullable: true)]
    private ?string $author;

    #[ORM\Column(type: "smallint", name: "release_year", nullable: true)]
    private ?int $release_year;

    #[ORM\Column(type: "string", name: "genre", nullable: true)]
    private ?string $genre;

    #[ORM\Column(type: "text", name: "description", nullable: true)]
    private ?string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->release_year;
    }

    public function setReleaseYear(?int $release_year): self
    {
        $this->release_year = $release_year;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): self
    {
        $this->genre = $genre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

}