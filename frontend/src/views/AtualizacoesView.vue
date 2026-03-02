<template>
  <section class="atualizacoes-page">

    <TopActions />

    <div class="container">

      <h1 class="page-title">Atualizações</h1>

      <div
        v-for="item in atualizacoes"
        :key="item.uuid"
        class="update-card"
      >

        <div class="data">
          {{ formatarData(item.created_at) }}
        </div>

        <div class="conteudo">

          <span v-if="item.isNova" class="badge-nova">
            Nova versão
          </span>

          <h2 class="titulo">
            {{ item.titulo }}
          </h2>

          <!-- 🔥 Markdown renderizado corretamente -->
          <div
            class="markdown"
            v-html="item.html"
          ></div>

          <div class="footer">

            <span class="autor">
              Por {{ item.usuario_nome }}
            </span>

            <a
              v-if="item.link_musica"
              :href="item.link_musica"
              target="_blank"
              class="musica"
            >
              🎵 Escutar música
            </a>

          </div>

        </div>

      </div>

    </div>

  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { marked } from "marked";
import TopActions from "@/components/layout/TopActions.vue";
import { listarAtualizacoes } from "@/services/atualizacao.service";

const atualizacoes = ref([]);

onMounted(async () => {
  const data = await listarAtualizacoes();

  atualizacoes.value = data
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .map((item, index) => ({
      ...item,
      html: marked.parse(item.conteudo_markdown || ""),
      isNova: index === 0
    }));
});

function formatarData(data) {
  return new Date(data).toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "short",
    year: "numeric"
  });
}
</script>

<style scoped>

.badge-nova {
  display: inline-block;
  background: linear-gradient(135deg, #c7a43a, #b89630);
  color: #fff;
  font-weight: 700;
  font-size: 13px;
  padding: 6px 14px;
  border-radius: 14px;
  margin-bottom: 18px;
  box-shadow: 0 8px 20px rgba(0,0,0,.25);
}

.atualizacoes-page {
  min-height: 100vh;
  background: url("/backgrounds/home-bg.svg") center/cover;
  padding: 120px 24px 80px;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
}

.page-title {
  font-size: 52px;
  color: #fff;
  margin-bottom: 60px;
  text-align: center;
  font-weight: 800;
}

/* CARD */

.update-card {
  display: flex;
  gap: 40px;
  margin-bottom: 80px;
}

/* DATA LATERAL */

.data {
  min-width: 140px;
  font-weight: 600;
  color: #ddd;
  font-size: 16px;
  margin-top: 8px;
}

/* CONTEUDO */

.conteudo {
  background: #fbf6e6;
  padding: 40px;
  border-radius: 28px;
  box-shadow: 0 30px 80px rgba(0,0,0,.3);
  flex: 1;
}

.titulo {
  font-size: 34px;
  font-weight: 800;
  margin-bottom: 20px;
  color: #222;
}

/* MARKDOWN */

.markdown :deep(h1),
.markdown :deep(h2),
.markdown :deep(h3) {
  margin-top: 24px;
}

.markdown :deep(p) {
  margin-bottom: 12px;
  line-height: 1.6;
}

.markdown :deep(ul) {
  padding-left: 20px;
  margin-bottom: 12px;
}

/* FOOTER */

.footer {
  margin-top: 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid rgba(0,0,0,.08);
  padding-top: 20px;
}

.autor {
  font-weight: 600;
  color: #555;
}

.musica {
  font-weight: 700;
  color: #c7a43a;
  text-decoration: none;
  transition: .2s ease;
}

.musica:hover {
  color: #b89630;
}

/* MOBILE */

@media (max-width: 900px) {
  .update-card {
    flex-direction: column;
  }

  .data {
    margin-bottom: 10px;
  }
}

@media (max-width: 600px) {
  .page-title {
    font-size: 36px;
  }

  .conteudo {
    padding: 24px;
  }

  .titulo {
    font-size: 26px;
  }
}
</style>