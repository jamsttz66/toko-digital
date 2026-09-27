<?php
/**
 * Navbar customer.
 * Mobile: offcanvas (PRD 46, 50)
 */
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo app_url('index.php'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="2" y="3" width="20" height="18" rx="4" stroke="#1a2332" stroke-width="1.6"/>
                <path d="M2 9h20" stroke="#1a2332" stroke-width="1.6"/>
                <path d="M8 13.5h8" stroke="#2f5d50" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <span>Toko Digital</span>
        </a>

        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#navOffcanvas"
                aria-controls="navOffcanvas" aria-label="Buka menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16" stroke="#1a2332" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'home' ? 'active' : ''; ?>"
                       href="<?php echo app_url('index.php'); ?>">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'produk' ? 'active' : ''; ?>"
                       href="<?php echo app_url('produk.php'); ?>">Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'kategori' ? 'active' : ''; ?>"
                       href="<?php echo app_url('kategori.php'); ?>">Kategori</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'faq' ? 'active' : ''; ?>"
                       href="<?php echo app_url('faq.php'); ?>">FAQ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'contact' ? 'active' : ''; ?>"
                       href="<?php echo app_url('contact.php'); ?>">Kontak</a>
                </li>
            </ul>

            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" href="<?php echo app_url('cart.php'); ?>">
                        Keranjang
                        <?php $cartCount = !empty($cartCount) ? $cartCount : 0; ?>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo e($_SESSION['user']['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-line shadow-sm">
                            <li><a class="dropdown-item" href="<?php echo app_url('library.php'); ?>">Produk Saya</a></li>
                            <li><a class="dropdown-item" href="<?php echo app_url('orders.php'); ?>">Pesanan</a></li>
                            <li><a class="dropdown-item" href="<?php echo app_url('profile.php'); ?>">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item" href="<?php echo app_url('admin/'); ?>">Admin Panel</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?php echo app_url('logout.php'); ?>">Keluar</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo app_url('login.php'); ?>">Masuk</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm ms-lg-1" href="<?php echo app_url('register.php'); ?>">Daftar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Offcanvas untuk mobile -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="navOffcanvas" aria-labelledby="navOffcanvasLabel">
    <div class="offcanvas-header border-bottom border-line">
        <h5 class="offcanvas-title" id="navOffcanvasLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column gap-1">
            <li class="nav-item"><a class="nav-link" href="<?php echo app_url('index.php'); ?>">Beranda</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo app_url('produk.php'); ?>">Produk</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo app_url('kategori.php'); ?>">Kategori</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo app_url('cart.php'); ?>">
                Keranjang<?php if (!empty($cartCount) && $cartCount > 0): ?> (<?php echo $cartCount; ?>)<?php endif; ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo app_url('faq.php'); ?>">FAQ</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo app_url('contact.php'); ?>">Kontak</a></li>
            <li><hr class="my-2"></li>
            <?php if (is_logged_in()): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo app_url('library.php'); ?>">Produk Saya</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo app_url('orders.php'); ?>">Pesanan</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo app_url('profile.php'); ?>">Profile</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="<?php echo app_url('logout.php'); ?>">Keluar</a></li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo app_url('login.php'); ?>">Masuk</a></li>
                <li class="nav-item"><a class="btn btn-primary w-100 mt-2" href="<?php echo app_url('register.php'); ?>">Daftar</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>
