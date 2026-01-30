<template>
    <div class="login-page">
        <div class="login-card">
            <div class="login-image">
                <img :src="imagemLogin" alt="Login" />
            </div>

            <div class="login-form">
                <h1>BEM VINDO MEU MANO!
                </h1>
                <p>
                    Caso seja membro realize o Login, caso não seja entre como visitante.
                </p>

                <form @submit.prevent="handleLogin">
                    <div class="field">
                        <label>Email</label>
                        <input v-model="email" type="email" placeholder="Entre com o Seu email" required />
                    </div>

                    <div class="field password-field">
                        <label>Password</label>

                        <div class="password-wrapper">
                            <input v-model="senha" :type="mostrarSenha ? 'text' : 'password'" placeholder="********"
                                required />

                            <span class="toggle-password" @click="toggleSenha">
                                {{ mostrarSenha ? "🙈" : "👁️" }}
                            </span>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading">
                        {{ loading ? "Entrando..." : "Entrar" }}
                    </button>
                </form>

                <div class="visitor">
                    Visitante? Bem vindo!
                    <span @click="entrarComoVisitante"> clique aqui </span>
                </div>

                <p v-if="erro" class="error">
                    {{ erro }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { login } from "@/services/auth.service";
import { useAuthStore } from "@/stores/auth";

const email = ref("");
const senha = ref("");
const erro = ref("");
const loading = ref(false);
const imagemLogin = ref("");
const mostrarSenha = ref(false);
const authStore = useAuthStore();

const router = useRouter();

function toggleSenha() {
    mostrarSenha.value = !mostrarSenha.value;
}

function escolherImagemRandomica() {
    const TOTAL_IMAGENS = 10;
    const numero = Math.floor(Math.random() * TOTAL_IMAGENS) + 1;

    imagemLogin.value = `/login/${numero}.jpg`;
}

async function handleLogin() {
    erro.value = "";
    loading.value = true;

    try {
        const response = await login(email.value, senha.value);

        authStore.setUsuario(response.usuario);

        router.push("/");

    } catch (e) {
        erro.value =
            e.response?.data?.message || "Erro ao realizar login.";
    } finally {
        loading.value = false;
    }
}

function entrarComoVisitante() {
    router.push("/");
}

onMounted(() => {
    escolherImagemRandomica();
});
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    background: url("/backgrounds/login-bg.svg") no-repeat center center;
    background-size: cover;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* CARD MAIOR */
.login-card {
    width: 880px;
    min-height: 720px;
    background: #ffffff;
    border-radius: 32px;
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.28);
    display: flex;
    flex-direction: column;
    animation:
        fadeUp 0.6s ease-out both
}

/* IMAGEM MAIS ALTA */
.login-image {
    height: 450px;
}

.login-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* FORM */
.login-form {
    padding: 40px 44px;
    text-align: center;
    flex: 1;
}

.login-form h1 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 10px;
}

.login-form p {
    font-size: 15px;
    color: #666;
    margin-bottom: 32px;
}

.field {
    margin-bottom: 22px;
    text-align: left;
}

.field label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
}

.field input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1px solid #ccc;
    font-size: 15px;
}

/* BOTÃO */
button {
    width: 50%;
    margin: 14px auto 0;
    padding: 14px 12px;
    border: none;
    border-radius: 16px;
    background: #c7a43a;
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.15s ease;
}

/* hover */
button:hover:not(:disabled) {
    background: #b89630;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
}

/* active */
button:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.14);
}

button:disabled {
    opacity: 0.7;
    cursor: default;
}


.visitor {
    margin-top: 22px;
    font-size: 14px;
    color: #555;
}

.visitor span {
    color: #c7a43a;
    font-weight: 600;
    cursor: pointer;
}

.error {
    margin-top: 14px;
    color: red;
    font-size: 14px;
}

.password-field .password-wrapper {
    position: relative;
}

.password-field input {
    padding-right: 44px;
    /* espaço para o ícone */
}

.toggle-password {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 16px;
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.toggle-password:hover {
    opacity: 1;
}


@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(24px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes float {
    0% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-6px);
    }

    100% {
        transform: translateY(0);
    }
}
</style>
