<?php
namespace App\DTO;
class HistoriaUpdateDTO
{
    public string $titulo;
    public ?string $descricaoCurta;
    public string $tipoHistoria;
    public array $usuarios;

    public function __construct(array $data)
    {
        $this->titulo = $data['titulo'];
        $this->descricaoCurta = $data['descricao_curta'] ?? null;
        $this->tipoHistoria = $data['tipo_historia'];
        $this->usuarios = $data['usuarios'] ?? [];
    }
}
