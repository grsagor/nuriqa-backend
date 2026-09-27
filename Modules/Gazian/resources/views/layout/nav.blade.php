<nav class="sb-topnav navbar navbar-expand navbar-dark" style="background-color:#0c4a6e;">
    <a class="navbar-brand ps-3" href="{{ route('gazian.admin.dashboard.index') }}">Gazian Water Admin</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
        <i class="fas fa-bars"></i>
    </button>
    <ul class="navbar-nav ms-auto me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="{{ route('admin.dashboard.index') }}">Nuriqa Admin</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <form action="{{ route('auth.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item text-start" style="border: none; background: none; width: 100%; text-align: left; padding: 0.25rem 1rem;">
                            Logout
                        </button>
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>
