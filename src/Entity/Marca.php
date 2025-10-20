<?php
// src/Entity/Marca.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarcaRepository")
 * @ORM\Table(name="marca")
 */
class Marca
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $significado;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $identificacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $web;

    /**
     * @ORM\Column(type="string", length=7, nullable=true)
     */
    private $color1;

    /**
     * @ORM\Column(type="string", length=7, nullable=true)
     */
    private $color2;

    /**
     * @ORM\Column(type="string", length=7, nullable=true)
     */
    private $color3;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Documento")
     * @ORM\JoinColumn(nullable=true)
     */
    private $documento;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
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

    public function getSignificado(): ?string
    {
        return $this->significado;
    }

    public function setSignificado(?string $significado): self
    {
        $this->significado = $significado;
        return $this;
    }

    public function getIdentificacion(): ?string
    {
        return $this->identificacion;
    }

    public function setIdentificacion(?string $identificacion): self
    {
        $this->identificacion = $identificacion;
        return $this;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setWeb(?string $web): self
    {
        $this->web = $web;
        return $this;
    }

    public function getColor1(): ?string
    {
        return $this->color1;
    }

    public function setColor1(?string $color1): self
    {
        $this->color1 = $color1;
        return $this;
    }

    public function getColor2(): ?string
    {
        return $this->color2;
    }

    public function setColor2(?string $color2): self
    {
        $this->color2 = $color2;
        return $this;
    }

    public function getColor3(): ?string
    {
        return $this->color3;
    }

    public function setColor3(?string $color3): self
    {
        $this->color3 = $color3;
        return $this;
    }

    public function getDocumento(): ?Documento
    {
        return $this->documento;
    }

    public function setDocumento(?Documento $documento): self
    {
        $this->documento = $documento;
        return $this;
    }
}