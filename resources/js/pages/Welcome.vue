<script setup lang="ts">
import { SharedData, User } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage<SharedData>()
const user = page.props.auth.user as User;
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <title>Sistem Penjadwalan Seminar</title>
    </Head>

    <header>
        <h1>Sistem Penjadwalan Seminar</h1>
        <nav>
            <a href="#features">Fitur</a>
            <a href="#about">Tentang</a>
            <a href="#contact">Kontak</a>
            <template v-if="!user">
                <a href="/login" class="btn-login">Masuk</a>
                <a href="/register" class="btn-login">Daftar</a>
            </template>
            <template v-else>
                <a href="/dashboard" class="btn-login">Dashboard</a>
            </template>
        </nav>
    </header>

    <section class="hero" role="banner" aria-label="Sistem Penjadwalan Seminar Hero">
        <h2>Jadwalkan Seminar Anda dengan Mudah & Efisien</h2>
        <p>Manajemen penjadwalan seminar dengan sistem yang intuitif dan cepat. Optimalisasi waktu dan sumber daya dengan teknologi modern.</p>
        <button class="cta-btn" onclick="alert('Fitur sedang dikembangkan!')">Mulai Sekarang</button>
    </section>

    <section id="features" class="features" aria-labelledby="features-title">
        <h3 id="features-title">Fitur Unggulan</h3>
        <div class="feature-list">
            <article class="feature-item" tabindex="0">
                <h4>Penjadwalan Otomatis</h4>
                <p>Atur jadwal seminar secara otomatis berdasarkan ketersediaan peserta dan ruang.</p>
            </article>
            <article class="feature-item" tabindex="0">
                <h4>Notifikasi Real-time</h4>
                <p>Dapatkan pemberitahuan segera tentang perubahan jadwal atau pengingat seminar.</p>
            </article>
            <article class="feature-item" tabindex="0">
                <h4>Manajemen Peserta</h4>
                <p>Kendalikan daftar peserta, konfirmasi kehadiran, dan statistik kehadiran secara mudah.</p>
            </article>
            <article class="feature-item" tabindex="0">
                <h4>Laporan Lengkap</h4>
                <p>Unduh laporan penjadwalan dan kehadiran seminar untuk analisis lebih lanjut.</p>
            </article>
        </div>
    </section>

    <section
        id="about"
        aria-labelledby="about-title"
        style="max-width: 720px; margin: 4rem auto 6rem; padding: 0 1rem; text-align: center; color: #d9e4f5"
    >
        <h3 id="about-title" style="font-weight: 600; font-size: 2rem; margin-bottom: 1rem">Tentang Sistem Kami</h3>
        <p style="font-weight: 300; font-size: 1.125rem; line-height: 1.6">
            Sistem penjadwalan seminar kami dirancang untuk mempermudah penyelenggaraan seminar dengan fitur-fitur modern yang membantu pengelolaan
            waktu dan peserta secara efektif. Dengan antarmuka yang ramah pengguna, Anda dapat merencanakan dan memonitor semua aktivitas seminar
            dalam satu platform tanpa kerepotan.
        </p>
    </section>

    <footer id="contact" aria-label="Kontak">&copy; 2024 Sistem Penjadwalan Seminar. All rights reserved.</footer>
</template>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;600&display=swap');
* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #4a90e2 0%, #022f52 100%);
    color: #f0f4f8;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
header {
    background: rgba(0, 0, 0, 0.3);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    backdrop-filter: blur(8px);
}
header h1 {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
    letter-spacing: 1.2px;
}
nav a {
    text-decoration: none;
    color: #f0f4f8;
    font-weight: 600;
    margin-left: 2rem;
    transition: color 0.3s ease;
}
nav a:hover,
nav a:focus {
    color: #ffbe00;
}
.hero {
    flex: 1;
    text-align: center;
    padding: 4rem 2rem 6rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.hero h2 {
    font-weight: 600;
    font-size: 2.75rem;
    margin-bottom: 1rem;
    line-height: 1.2;
}
.hero p {
    font-weight: 300;
    font-size: 1.25rem;
    max-width: 480px;
    margin: 0 auto 3rem;
    color: #d9e4f5;
}
.cta-btn {
    background-color: #ffbe00;
    color: #022f52;
    font-weight: 700;
    font-size: 1.25rem;
    border: none;
    border-radius: 36px;
    padding: 1rem 3rem;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(255, 190, 0, 0.4);
    transition:
        background-color 0.3s ease,
        box-shadow 0.3s ease;
}
.cta-btn:hover,
.cta-btn:focus {
    background-color: #e6a700;
    box-shadow: 0 8px 20px rgba(230, 167, 0, 0.6);
    outline: none;
}
.features {
    background: #f0f4f8;
    color: #022f52;
    padding: 4rem 2rem 6rem;
    max-width: 960px;
    margin: 0 auto;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(2, 47, 82, 0.15);
}
.features h3 {
    text-align: center;
    font-weight: 600;
    font-size: 2rem;
    margin-bottom: 3rem;
}
.feature-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 2rem;
}
.feature-item {
    background: #ffffff;
    border-radius: 12px;
    flex: 1 1 280px;
    padding: 2rem;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}
.feature-item:hover {
    transform: translateY(-8px);
}
.feature-item h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-weight: 600;
    font-size: 1.25rem;
}
.feature-item p {
    font-weight: 300;
    font-size: 1rem;
    color: #555;
    line-height: 1.5;
}
footer {
    background: rgba(0, 0, 0, 0.3);
    text-align: center;
    padding: 1.5rem 1rem;
    font-weight: 300;
    font-size: 0.9rem;
    color: #cfd8e6;
    backdrop-filter: blur(8px);
}
/* Responsive */
@media (max-width: 600px) {
    .hero h2 {
        font-size: 2rem;
    }
    .cta-btn {
        font-size: 1rem;
        padding: 0.75rem 2rem;
    }
    .features {
        padding: 2rem 1rem 4rem;
        margin: 2rem 1rem;
    }
    .feature-list {
        flex-direction: column;
        gap: 1.5rem;
    }
}
</style>
