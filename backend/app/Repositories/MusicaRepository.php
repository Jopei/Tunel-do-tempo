<?php

namespace App\Repositories;

use App\Models\Musica;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\Mapping\MusicaMapping;
use App\Support\Sanitizador;

class MusicaRepository
{
    public function all(): Collection
    {
        $musicas = Musica::all();

        return $musicas->map(function ($musica) {
            $musica->url_publica = $this->getPublicUrl($musica->{MusicaMapping::PATH});
            return $musica;
        });
    }

    public function paginar(?string $q, string $ordem, int $page, int $perPage)
    {
        $query = Musica::query()->with('capa')
            ->when($q, function ($query) use ($q) {
                $like = Sanitizador::like($q);
                $query->where(fn ($w) => $w->where(MusicaMapping::TITULO, 'like', $like)
                    ->orWhere(MusicaMapping::ARTISTA, 'like', $like)
                    ->orWhere(MusicaMapping::ALBUM, 'like', $like));
            });

        match ($ordem) {
            'antigas' => $query->orderBy(MusicaMapping::CREATED_AT)->orderBy('id'),
            'titulo' => $query->orderBy(MusicaMapping::TITULO)->orderByDesc('id'),
            'artista' => $query->orderBy(MusicaMapping::ARTISTA)->orderBy(MusicaMapping::TITULO)->orderByDesc('id'),
            'album' => $query->orderBy(MusicaMapping::ALBUM)->orderBy(MusicaMapping::TITULO)->orderByDesc('id'),
            default => $query->orderByDesc(MusicaMapping::CREATED_AT)->orderByDesc('id'),
        };

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function buscarPorUuid(string $uuid): Musica
    {
        return Musica::where(MusicaMapping::UUID, $uuid)->firstOrFail();
    }

    public function atualizarCampos(Musica $musica, array $campos): Musica
    {
        $musica->update($campos);

        return $musica->refresh();
    }

    public function excluir(Musica $musica): void
    {
        $musica->delete();
    }

    public function find(int $id): ?Musica
    {
        $musica = Musica::find($id);

        if ($musica) {
            $musica->url_publica = $this->getPublicUrl($musica->{MusicaMapping::PATH});
        }

        return $musica;
    }

    public function create(array $data, ?UploadedFile $file = null): Musica
    {
        // path ja pode vir salvo pelo service; so gera um novo se for enviado o arquivo
        $path = $data[MusicaMapping::PATH] ?? null;

        if ($file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/musicas', $filename);
            $path = str_replace('public/', '', $path);
        }

        $musica = Musica::create([
            MusicaMapping::UUID => $data[MusicaMapping::UUID] ?? Str::uuid(),
            MusicaMapping::TITULO => $data[MusicaMapping::TITULO] ?? null,
            MusicaMapping::PATH => $path,
            MusicaMapping::ARTISTA => $data[MusicaMapping::ARTISTA] ?? null,
            MusicaMapping::ALBUM => $data[MusicaMapping::ALBUM] ?? null,
            MusicaMapping::ANO => $data[MusicaMapping::ANO] ?? null,
            MusicaMapping::DURACAO_SEGUNDOS => $data[MusicaMapping::DURACAO_SEGUNDOS] ?? null,
            MusicaMapping::CAPA_FOTO_ID => $data[MusicaMapping::CAPA_FOTO_ID] ?? null,
        ]);

        $musica->url_publica = $this->getPublicUrl($musica->{MusicaMapping::PATH});

        return $musica;
    }

    public function update(Musica $musica, array $data): Musica
    {
        $musica->update([
            MusicaMapping::TITULO => $data[MusicaMapping::TITULO] ?? $musica->{MusicaMapping::TITULO},
        ]);

        $musica->url_publica = $this->getPublicUrl($musica->{MusicaMapping::PATH});

        return $musica;
    }

    public function delete(Musica $musica): bool
    {
        if ($musica->{MusicaMapping::PATH} && Storage::exists('public/' . $musica->{MusicaMapping::PATH})) {
            Storage::delete('public/' . $musica->{MusicaMapping::PATH});
        }

        return $musica->delete();
    }

    public function download(int $id)
    {
        $musica = $this->find($id);

        if (!$musica || !$musica->{MusicaMapping::PATH}) {
            return null;
        }

        $filePath = 'public/' . $musica->{MusicaMapping::PATH};

        if (!Storage::exists($filePath)) {
            return null;
        }

        return Storage::download($filePath, basename($filePath));
    }

    private function getPublicUrl(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }
}