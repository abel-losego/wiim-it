<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ArticleRepository::class)
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $id_stripe;

    /**
     * @ORM\Column(type="float")
     */
    private $price;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $best;

    /**
     * @ORM\ManyToMany(targetEntity=Service::class, inversedBy="articles")
     */
    private $services;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $id_doli;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ref;


    public function __construct()
    {
        $this->services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdStripe(): ?string
    {
        return $this->id_stripe;
    }

    public function setIdStripe(string $id_stripe): self
    {
        $this->id_stripe = $id_stripe;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBest(): ?bool
    {
        return $this->best;
    }

    public function setBest(?bool $best): self
    {
        $this->best = $best;

        return $this;
    }

    /**
     * @return Collection|service[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services[] = $service;
        }

        return $this;
    }

    public function removeService(service $service): self
    {
        $this->services->removeElement($service);

        return $this;
    }

    public function getIdDoli(): ?string
    {
        return $this->id_doli;
    }

    public function setIdDoli(string $id_doli): self
    {
        $this->id_doli = $id_doli;

        return $this;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function setRef(string $ref): self
    {
        $this->ref = $ref;

        return $this;
    }

    
}
