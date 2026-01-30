<?php

namespace App\Repositories;

use App\Models\Musica;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\Mapping\MusicaMapping;

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
        $path = null;

        if ($file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/musicas', $filename);
            $path = str_replace('public/', '', $path);
        }

        $musica = Musica::create([
            MusicaMapping::UUID => $data[MusicaMapping::UUID] ?? Str::uuid(),
            MusicaMapping::TITULO => $data[MusicaMapping::TITULO] ?? null,
            MusicaMapping::PATH => $path,
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