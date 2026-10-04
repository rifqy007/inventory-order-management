<?php

declare(strict_types=1);

use App\Support\Http;

/**
 * Halaman Login
 *
 * Validasi dilakukan pada dua lapisan:
 * 1. JavaScript menampilkan pesan Bahasa Indonesia tanpa memuat ulang halaman.
 * 2. Backend tetap memeriksa email, password, status akun, CSRF, dan session.
 *
 * Atribut novalidate menonaktifkan balon validasi bawaan browser yang bahasanya
 * mengikuti bahasa browser. Validasi keamanan tetap menjadi tanggung jawab backend.
 */
?>

<section class="login-page">
    <div class="login-showcase" aria-hidden="true">
        <span class="showcase-eyebrow">INVENTORY CONTROL</span>
        <h2>Semua pergerakan stok, lebih mudah dipantau.</h2>
        <p>Kelola produk, gudang, dan pesanan dalam satu ruang kerja yang ringkas.</p>
        <div class="showcase-card">
            <span class="showcase-icon">↗</span>
            <span><b>Operasional terarah</b><small>Stok dan pesanan tersusun rapi</small></span>
        </div>
        <div class="showcase-dots"><i></i><i></i><i></i></div>
    </div>
    <div class="login-card">
        <div class="login-brand">
            <div class="login-logo" aria-hidden="true">
                <span class="login-logo-box"></span>
                <span class="login-logo-box"></span>
                <span class="login-logo-box"></span>
                <span class="login-logo-box"></span>
            </div>

            <div>
                <p class="login-brand-name">Inventory &amp; Order</p>
                <p class="login-brand-description">Management System</p>
            </div>
        </div>

        <div class="login-heading">
            <h1>Selamat Datang</h1>
            <p>Masukkan email dan password untuk mengakses aplikasi.</p>
        </div>

        <form
            id="login-form"
            method="post"
            action="/login"
            class="login-form"
            novalidate>
            <input
                type="hidden"
                name="_token"
                value="<?= Http::e(Http::csrf()) ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    placeholder="test@example.com"
                    autocomplete="email"
                    aria-describedby="email-error"
                    required
                    autofocus>
                <p id="email-error" class="field-error" aria-live="polite"></p>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    aria-describedby="password-error"
                    required>
                <p id="password-error" class="field-error" aria-live="polite"></p>
            </div>

            <?php if ($flash !== null && $flash['type'] === 'error'): ?>
                <div class="login-error" role="alert">
                    <?= Http::e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="login-submit">Masuk</button>
        </form>

        <p class="login-footer">Inventory &amp; Order Management System</p>
    </div>
</section>

<script>
    (() => {
        'use strict';

        const form = document.getElementById('login-form');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const emailError = document.getElementById('email-error');
        const passwordError = document.getElementById('password-error');

        if (!form || !email || !password || !emailError || !passwordError) {
            return;
        }

        const showError = (input, errorElement, message) => {
            input.classList.add('input-error');
            input.setAttribute('aria-invalid', 'true');
            errorElement.textContent = message;
        };

        const clearError = (input, errorElement) => {
            input.classList.remove('input-error');
            input.removeAttribute('aria-invalid');
            errorElement.textContent = '';
        };

        const validateEmail = () => {
            clearError(email, emailError);
            const value = email.value.trim();
            if (email.value !== value) {
                email.value = value;
            }

            if (value === '') {
                showError(email, emailError, 'Email wajib diisi.');
                return false;
            }

            if (!email.validity.valid) {
                showError(email, emailError, 'Format email tidak valid. Contoh: test@example.com');
                return false;
            }

            return true;
        };

        const validatePassword = () => {
            clearError(password, passwordError);

            if (password.value === '') {
                showError(password, passwordError, 'Password wajib diisi.');
                return false;
            }

            return true;
        };

        email.addEventListener('input', validateEmail);
        password.addEventListener('input', validatePassword);

        form.addEventListener('submit', (event) => {
            const emailValid = validateEmail();
            const passwordValid = validatePassword();

            if (!emailValid || !passwordValid) {
                event.preventDefault();
                (emailValid ? password : email).focus();
            }
        });
    })();
</script>
