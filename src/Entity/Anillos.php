<?php
// src/Entity/Anillos.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AnillosRepository")
 * @ORM\Table(name="anillos")
 */
class Anillos
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $cantidad;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $forma;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $texto;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Documento")
     * @ORM\JoinColumn(nullable=true)
     */
    private $imagen;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $color_bordes;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): self
    {
        $this->cantidad = $cantidad;
        return $this;
    }

    public function getForma(): ?string
    {
        return $this->forma;
    }

    public function setForma(string $forma): self
    {
        $this->forma = $forma;
        return $this;
    }

    public function getTexto(): ?string
    {
        return $this->texto;
    }

    public function setTexto(string $texto): self
    {
        $this->texto = $texto;
        return $this;
    }

    public function getImagen(): ?Documento
    {
        return $this->imagen;
    }

    public function setImagen(?Documento $imagen): self
    {
        $this->imagen = $imagen;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getColorBordes(): ?string
    {
        return $this->color_bordes;
    }

    public function setColorBordes(string $color_bordes): self
    {
        $this->color_bordes = $color_bordes;
        return $this;
    }
}