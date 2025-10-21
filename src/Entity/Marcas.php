<?php
// src/Entity/Marcas.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarcasRepository")
 * @ORM\Table(name="marcas")
 */
class Marcas
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
     * @ORM\OneToMany(targetEntity="Anillos", mappedBy="marca" , cascade={"remove"})
     * @OrderBy({"id" = "ASC"})
     */
    private $anillos;

    /**
     * @ORM\OneToMany(targetEntity="Cajas", mappedBy="marca" , cascade={"remove"})
     * @OrderBy({"id" = "ASC"})
     */
    private $cajas;

    /**
     * @ORM\OneToMany(targetEntity="Vitolarios", mappedBy="marca" , cascade={"remove"})
     * @OrderBy({"id" = "ASC"})
     */
    private $vitolarios;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Documento")
     * @ORM\JoinColumn(nullable=true)
     */
    private $documento;

    public function __construct()
    {
        $this->anillos = new ArrayCollection();
        $this->cajas = new ArrayCollection();
        $this->vitolarios = new ArrayCollection();
    }

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

    /**
     * Add anillo
     *
     * @param \App\Entity\Anillos $anillo
     *
     * @return Marcas
     */
    public function addAnillo(\App\Entity\Anillos $anillo)
    {
        $this->anillos[] = $anillo;

        return $this;
    }

    /**
     * Remove anillos
     *
     * @param \App\Entity\Anillos $anillo
     */
    public function removeAnillo(\App\Entity\Anillos $anillo)
    {
        $this->anillos->removeElement($anillo);
    }

    /**
     * Get anillos
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnillos()
    {
        return $this->anillos;
    }

    /**
     * Add caja
     *
     * @param \App\Entity\Cajas $caja
     *
     * @return Marcas
     */
    public function addCaja(\App\Entity\Cajas $caja)
    {
        $this->cajas[] = $caja;

        return $this;
    }

    /**
     * Remove caja
     *
     * @param \App\Entity\Cajas $caja
     */
    public function removeCaja(\App\Entity\Cajas $caja)
    {
        $this->cajas->removeElement($caja);
    }

    /**
     * Get caja
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCajas()
    {
        return $this->cajas;
    }

    /**
     * Add vitolario
     *
     * @param \App\Entity\Vitolarios $vitolario
     *
     * @return Marcas
     */
    public function addVitolario(\App\Entity\Vitolarios $vitolario)
    {
        $this->vitolarios[] = $vitolario;

        return $this;
    }

    /**
     * Remove vitolario
     *
     * @param \App\Entity\Vitolarios $vitolario
     */
    public function remoVitolario(\App\Entity\Vitolarios $vitolario)
    {
        $this->anillos->removeElement($vitolario);
    }

    /**
     * Get vitolarios
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVitolario()
    {
        return $this->vitolarios;
    }
}