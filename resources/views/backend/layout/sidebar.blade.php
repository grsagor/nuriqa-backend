<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Core</div>
                <a class="nav-link {{ Request::segment(2) == 'dashboard' ? 'active' : '' }}" href="{{ route('admin.dashboard.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>

                <div class="sb-sidenav-menu-heading">Users</div>
                {{-- <a class="nav-link {{ Request::segment(2) == 'languages' ? 'active' : '' }}" href="{{ route('admin.languages.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-language"></i></div>
                    Languages
                </a> --}}
                <a class="nav-link {{ Request::segment(2) == 'roles' ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-tag"></i></div>
                    Roles
                </a>
                <a class="nav-link {{ Request::segment(2) == 'users' ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-user"></i></div>
                    Users
                </a>

                {{-- User Management Started --}}
                {{-- <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsers"
                    aria-expanded="{{ in_array(Request::segment(2), ['roles', 'users']) ? 'true' : 'false' }}" aria-controls="collapseUsers">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                    Users
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse {{ in_array(Request::segment(2), ['roles', 'users']) ? 'show' : '' }}" id="collapseUsers" aria-labelledby="headingOne"
                    data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link {{ Request::segment(2) == 'roles' ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">Role Management</a>
                        <a class="nav-link {{ Request::segment(2) == 'users' ? 'active' : '' }}" href="{{ route('admin.users.index') }}">User Management</a>
                    </nav>
                </div> --}}
                {{-- User Management Ended --}}

                <div class="sb-sidenav-menu-heading">Products</div>
                {{-- <a class="nav-link {{ Request::segment(2) == 'brands' ? 'active' : '' }}" href="{{ route('admin.brands.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-tag"></i></div>
                    Brands
                </a> --}}
                <a class="nav-link {{ Request::segment(2) == 'conditions' ? 'active' : '' }}" href="{{ route('admin.conditions.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                    Conditions
                </a>
                <a class="nav-link {{ Request::segment(2) == 'categories' ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-layer-group"></i></div>
                    Categories
                </a>
                <a class="nav-link {{ Request::segment(2) == 'sizes' ? 'active' : '' }}" href="{{ route('admin.sizes.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-ruler"></i></div>
                    Sizes
                </a>
                <a class="nav-link {{ Request::segment(2) == 'products' ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                    Products
                </a>

                {{-- <div class="sb-sidenav-menu-heading">Interface</div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts"
                    aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                    Layouts
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne"
                    data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="layout-static.html">Static Navigation</a>
                        <a class="nav-link" href="layout-sidenav-light.html">Light Sidenav</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages"
                    aria-expanded="false" aria-controls="collapsePages">
                    <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                    Pages
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapsePages" aria-labelledby="headingTwo"
                    data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionPages">
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                            data-bs-target="#pagesCollapseAuth" aria-expanded="false" aria-controls="pagesCollapseAuth">
                            Authentication
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="pagesCollapseAuth" aria-labelledby="headingOne"
                            data-bs-parent="#sidenavAccordionPages">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="login.html">Login</a>
                                <a class="nav-link" href="register.html">Register</a>
                                <a class="nav-link" href="password.html">Forgot Password</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                            data-bs-target="#pagesCollapseError" aria-expanded="false"
                            aria-controls="pagesCollapseError">
                            Error
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="pagesCollapseError" aria-labelledby="headingOne"
                            data-bs-parent="#sidenavAccordionPages">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="401.html">401 Page</a>
                                <a class="nav-link" href="404.html">404 Page</a>
                                <a class="nav-link" href="500.html">500 Page</a>
                            </nav>
                        </div>
                    </nav>
                </div>
                <div class="sb-sidenav-menu-heading">Addons</div>
                <a class="nav-link" href="charts.html">
                    <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                    Charts
                </a>
                <a class="nav-link" href="tables.html">
                    <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                    Tables
                </a> --}}
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            {{ Auth::user()->name }}
        </div>
    </nav>
</div>
