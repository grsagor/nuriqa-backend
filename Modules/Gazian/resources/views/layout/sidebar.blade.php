<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion" style="background-color:#082f49;">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Gazian Water</div>
                <a class="nav-link {{ request()->routeIs('gazian.admin.dashboard.*') ? 'active' : '' }}"
                   href="{{ route('gazian.admin.dashboard.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('gazian.admin.newsletter-subscribers.*') ? 'active' : '' }}"
                   href="{{ route('gazian.admin.newsletter-subscribers.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-envelope"></i></div>
                    Newsletter
                </a>
                <a class="nav-link {{ request()->routeIs('gazian.admin.trade-enquiries.*') ? 'active' : '' }}"
                   href="{{ route('gazian.admin.trade-enquiries.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-handshake"></i></div>
                    Trade Enquiries
                </a>
            </div>
        </div>
        <div class="sb-sidenav-footer" style="background-color:#061f31;">
            <div class="small">Logged in as</div>
            {{ Auth::user()->name ?? 'Admin' }}
        </div>
    </nav>
</div>
