<?php

namespace App\Model\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Model\Database\Repository\BookRepository')]
#[ORM\Table(name: 'book')]
class Book extends BaseEntity
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer')]
	#[ORM\GeneratedValue(strategy: 'AUTO')]
	private int $id;

	#[ORM\Column(type: 'string', name: 'name', nullable: true)]
	private string|null $name;

	#[ORM\Column(type: 'string', name: 'author', nullable: true)]
	private string|null $author;

	#[ORM\Column(type: 'smallint', name: 'release_year', nullable: true)]
	private int|null $release_year;

	#[ORM\Column(type: 'string', name: 'genre', nullable: true)]
	private string|null $genre;

	#[ORM\Column(type: 'text', name: 'description', nullable: true)]
	private string|null $description;

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function setName(string|null $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getAuthor(): string|null
	{
		return $this->author;
	}

	public function setAuthor(string|null $author): self
	{
		$this->author = $author;

		return $this;
	}

	public function getReleaseYear(): int|null
	{
		return $this->release_year;
	}

	public function setReleaseYear(int|null $release_year): self
	{
		$this->release_year = $release_year;

		return $this;
	}

	public function getGenre(): string|null
	{
		return $this->genre;
	}

	public function setGenre(string|null $genre): self
	{
		$this->genre = $genre;

		return $this;
	}

	public function getDescription(): string|null
	{
		return $this->description;
	}

	public function setDescription(string|null $description): self
	{
		$this->description = $description;

		return $this;
	}
}
