<?php
// src/Entity/Vitolarios.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VitolariosRepository")
 * @ORM\Table(name="vitolarios")
 */
class Vitolarios
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
    private $nombre;

    /**
     * @ORM\Column(type="integer")
     */
    private $cepo;

    /**
     * @ORM\Column(type="float")
     */
    private $diametro;

    /**
     * @ORM\Column(type="integer")
     */
    private $largo;

    /**
     * @ORM\Column(type="integer")
     */
    private $fortaleza;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getCepo(): ?int
    {
        return $this->cepo;
    }

    public function setCepo(int $cepo): self
    {
        $this->cepo = $cepo;
        return $this;
    }

    public function getDiametro(): ?float
    {
        return $this->diametro;
    }

    public function setDiametro(float $diametro): self
    {
        $this->diametro = $diametro;
        return $this;
    }

    public function getLargo(): ?int
    {
        return $this->largo;
    }

    public function setLargo(int $largo): self
    {
        $this->largo = $largo;
        return $this;
    }

    public function getFortaleza(): ?int
    {
        return $this->fortaleza;
    }

    public function setFortaleza(int $fortaleza): self
    {
        $this->fortaleza = $fortaleza;
        return $this;
    }


}