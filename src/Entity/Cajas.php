<?php
// src/Entity/Cajas.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CajasRepository")
 * @ORM\Table(name="cajas")
 */
class Cajas
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
    private $cant_puros;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $estilo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $detalle_int;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $detalle_ext;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $madera;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $texto;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCantPuros(): ?int
    {
        return $this->cant_puros;
    }

    public function setCantPuros(int $cant_puros): self
    {
        $this->cant_puros = $cant_puros;
        return $this;
    }

    public function getEstilo(): ?string
    {
        return $this->estilo;
    }

    public function setEstilo(string $estilo): self
    {
        $this->estilo = $estilo;
        return $this;
    }

    public function getDetalleInt(): ?string
    {
        return $this->detalle_int;
    }

    public function setDetalleInt(string $detalle_int): self
    {
        $this->detalle_int = $detalle_int;
        return $this;
    }

    public function getDetalleExt(): ?string
    {
        return $this->detalle_ext;
    }

    public function setDetalleExt(string $detalle_ext): self
    {
        $this->detalle_ext = $detalle_ext;
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

    public function getMadera(): ?string
    {
        return $this->madera;
    }

    public function setMadera(string $madera): self
    {
        $this->madera = $madera;
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
}