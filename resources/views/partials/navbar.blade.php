<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
            <i class="bi bi-box-seam"></i>
            <span>Controle de Estoque</span>
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarPrincipal"
            aria-controls="navbarPrincipal"
            aria-expanded="false"
            aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/') }}">Início</a>
                </li>
            </ul>

            @auth
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                            href="#"
                            id="menuUsuario"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuUsuario">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right me-1"></i> Sair
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            @endauth
        </div>
    </div>
</nav>
