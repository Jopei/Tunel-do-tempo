<?php

namespace App\Support;

use Mews\Purifier\Facades\Purifier;

/**
 * Sanitizacao de conteudo no servidor (defesa em profundidade, alem do DOMPurify do frontend).
 */
class Sanitizador
{
    /**
     * HTML rico: mantem apenas tags/atributos do perfil "conteudo" (config/purifier.php).
     */
    public static function html(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }

        return (string) Purifier::clean(self::normalizarListasQuill($valor), 'conteudo');
    }

    /**
     * Markdown: remove HTML perigoso, mas preserva `>` (citacoes) que o purifier escaparia.
     */
    public static function markdown(?string $valor): ?string
    {
        $limpo = self::html($valor);

        return $limpo === null ? null : str_replace('&gt;', '>', $limpo);
    }

    /**
     * Texto puro: sem nenhuma tag.
     */
    public static function texto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return trim(strip_tags($valor));
    }

    /**
     * Escapa curingas de LIKE para buscas literais.
     */
    public static function like(string $termo): string
    {
        return '%' . addcslashes($termo, '%_\\') . '%';
    }

    /**
     * Quill 2 emite <ol><li data-list="bullet|ordered">. Converte para <ul>/<ol>
     * reais, dividindo listas mistas em blocos consecutivos, e remove data-list.
     */
    public static function normalizarListasQuill(string $html): string
    {
        if (!str_contains($html, 'data-list')) {
            return $html;
        }

        $doc = new \DOMDocument();
        $anterior = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__raiz">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $raiz = $doc->getElementById('__raiz');
        if (!$raiz) {
            return $html;
        }

        $listas = [];
        foreach ($doc->getElementsByTagName('ol') as $l) {
            $listas[] = $l;
        }
        foreach ($doc->getElementsByTagName('ul') as $l) {
            $listas[] = $l;
        }

        foreach ($listas as $lista) {
            $itens = [];
            foreach ($lista->childNodes as $filho) {
                if ($filho instanceof \DOMElement && strtolower($filho->tagName) === 'li') {
                    $itens[] = $filho;
                }
            }
            if (!$itens) {
                continue;
            }

            $blocos = [];
            $tipoAtual = null;
            foreach ($itens as $li) {
                $tipo = $li->hasAttribute('data-list')
                    ? ($li->getAttribute('data-list') === 'ordered' ? 'ol' : 'ul')
                    : strtolower($lista->tagName);
                $li->removeAttribute('data-list');
                if ($tipo !== $tipoAtual) {
                    $blocos[] = ['tipo' => $tipo, 'itens' => []];
                    $tipoAtual = $tipo;
                }
                $blocos[count($blocos) - 1]['itens'][] = $li;
            }

            foreach ($blocos as $bloco) {
                $nova = $doc->createElement($bloco['tipo']);
                foreach ($bloco['itens'] as $li) {
                    $nova->appendChild($li);
                }
                $lista->parentNode->insertBefore($nova, $lista);
            }
            $lista->parentNode->removeChild($lista);
        }

        $saida = '';
        foreach ($raiz->childNodes as $no) {
            $saida .= $doc->saveHTML($no);
        }

        return $saida;
    }
}
