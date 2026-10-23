<?php

namespace App\Actions\Usuario;


use App\Models\Chaves;
use App\Services\UsuarioService;
use App\Services\FotoService;
use App\DTO\UsuarioCreateDTO;
use App\DTO\FotoCreateDTO;
use Illuminate\Support\Facades\DB;
use App\Enums\TipoUsuarioEnum;
use App\Models\TipoUsuario;
use App\Exceptions\ChaveInvalidaException;

class CriarUsuarioAction
{
    public function __construct(
        private UsuarioService $usuarioService,
        private FotoService $fotoService,
    ) {}

    public function execute(array $data, $imagem)
    {
        return DB::transaction(function () use ($data, $imagem) {

            $chave = Chaves::where('nome', $data['chave_nome'])
                ->where('codigo', $data['chave_codigo'])
                ->where('quebra', false)
                ->first();

            if (!$chave) {
                throw new ChaveInvalidaException('Chave inválida ou já utilizada.');
            }

            // Papel definido pelo servidor: a tabela `chaves` nao tem papel associado, entao todo
            // convite cria MEMBRO_RUA. Promocao a ADM so por um administrador existente.
            $tipoUsuarioId = TipoUsuario::where('nome', TipoUsuarioEnum::MEMBRO_RUA->value)->value('id');

            if (!$tipoUsuarioId) {
                throw new \RuntimeException('Tipo de usuário padrão não configurado.');
            }

            $usuarioDTO = new UsuarioCreateDTO(
                nome: $data['nome'],
                email: $data['email'],
                senha: $data['senha'],
                tipoUsuarioId: $tipoUsuarioId,
                aniversario: $data['aniversario'] ?? null,
                telefone: $data['telefone'] ?? null,
                descricao: $data['descricao'] ?? null,
            );

            $usuario = $this->usuarioService->criarUsuario($usuarioDTO);

            $fotoDTO = new FotoCreateDTO(
                imagem: $imagem,
                tipoImagemId: 1,
                usuariosUuid: [$usuario->uuid],
                titulo: 'Foto de Perfil',
                descricao: 'Imagem de perfil do usuário',
            );

            $this->fotoService->criarFoto($fotoDTO, $usuario);

            $chave->update([
                'quebra' => true
            ]);

            return $usuario;
        });
    }
}