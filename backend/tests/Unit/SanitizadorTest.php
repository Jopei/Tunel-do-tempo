<?php

namespace Tests\Unit;

use App\Support\Sanitizador;
use Tests\TestCase;

class SanitizadorTest extends TestCase
{
    public function test_html_remove_script_e_handlers(): void
    {
        $limpo = Sanitizador::html('<p onclick="x()">Oi</p><script>alert(1)</script><img src="a.png" onerror="alert(1)" alt="a">');

        $this->assertStringNotContainsString('script', $limpo);
        $this->assertStringNotContainsString('onerror', $limpo);
        $this->assertStringNotContainsString('onclick', $limpo);
        $this->assertStringContainsString('<p>Oi</p>', $limpo);
    }

    public function test_html_remove_javascript_uri(): void
    {
        $limpo = Sanitizador::html('<a href="javascript:alert(1)">x</a>');

        $this->assertStringNotContainsString('javascript:', $limpo);
    }

    public function test_html_preserva_formatacao_basica(): void
    {
        $limpo = Sanitizador::html('<h2>Titulo</h2><p><strong>negrito</strong> e <em>italico</em></p>');

        $this->assertStringContainsString('<h2>Titulo</h2>', $limpo);
        $this->assertStringContainsString('<strong>negrito</strong>', $limpo);
    }

    public function test_markdown_preserva_citacao_mas_remove_script(): void
    {
        $limpo = Sanitizador::markdown("> citacao\n\n<script>alert(1)</script>**ok**");

        $this->assertStringContainsString('> citacao', $limpo);
        $this->assertStringNotContainsString('<script', $limpo);
        $this->assertStringContainsString('**ok**', $limpo);
    }

    public function test_texto_remove_tags(): void
    {
        $this->assertSame('Oialert(1)', Sanitizador::texto(' <b>Oi</b><script>alert(1)</script> '));
    }

    public function test_like_escapa_curingas(): void
    {
        $this->assertSame('%a\%b\_c%', Sanitizador::like('a%b_c'));
    }

    public function test_lista_quill2_bullet_vira_ul_sem_data_list(): void
    {
        $limpo = Sanitizador::html('<ol><li data-list="bullet">a</li><li data-list="bullet">b</li></ol>');

        $this->assertStringContainsString('<ul>', $limpo);
        $this->assertStringNotContainsString('<ol>', $limpo);
        $this->assertStringNotContainsString('data-list', $limpo);
    }

    public function test_lista_mista_e_dividida_em_blocos(): void
    {
        $limpo = Sanitizador::html('<ol><li data-list="ordered">1</li><li data-list="bullet">x</li><li data-list="bullet">y</li><li data-list="ordered">2</li></ol>');

        $this->assertSame(2, substr_count($limpo, '<ol>'));
        $this->assertSame(1, substr_count($limpo, '<ul>'));
        $this->assertMatchesRegularExpression('#<ol>\s*<li>1</li>\s*</ol>\s*<ul>#', $limpo);
    }

    public function test_preserva_alinhamento_e_indentacao_quill_e_remove_outras_classes(): void
    {
        $limpo = Sanitizador::html('<p class="ql-align-center evil">oi</p><ol><li class="ql-indent-2">x</li></ol>');

        $this->assertStringContainsString('ql-align-center', $limpo);
        $this->assertStringContainsString('ql-indent-2', $limpo);
        $this->assertStringNotContainsString('evil', $limpo);
    }
}
