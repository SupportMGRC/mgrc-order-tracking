<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box d-flex justify-content-center align-items-center">
        <!-- Dark Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/mgrc/MGRC-logo-only.png') }}" alt="" height="40">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/mgrc/MGRC white font.png') }}" alt="" height="50">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/mgrc/MGRC-logo-only.png') }}" alt="" height="40">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/mgrc/MGRC white font.png') }}" alt="" height="50">
            </span>
        </a>
        {{-- Hidden, not removed: app.js calls getElementById("vertical-hover") with no
             null check and would throw, breaking the rest of its load handler. --}}
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover ms-4 d-none" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                
                {{-- Dashboard - Available for all authenticated users --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('dashboard') }}">
                        <i class="las la-tachometer-alt"></i> <span data-key="t-calendar">Dashboard</span>
                    </a>
                </li>

                <li class="menu-title"><span data-key="t-order">Order</span></li>
                
                {{-- New Order - Available for Medical Affairs, Business Development, admin, and superadmin --}}
                @if(Auth::user()->department == 'Medical Affairs' || Auth::user()->department == 'Business Development' || Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('neworder') }}">
                        <i class="las la-plus-circle"></i> <span data-key="t-new-order">New Order</span>
                    </a>
                </li>
                @endif
                
                {{-- Order History - Available for all authenticated users. Every
                     department and both roles see every order, so the label is
                     the same for everyone. --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('orderhistory') }}">
                        <i class="las la-history"></i> <span data-key="t-order-history">Order History</span>
                    </a>
                </li>

                {{-- Pickup - every authenticated user can request a pickup.
                     Pickup History is scoped in PickupController (MA/BD see own). --}}
                @php
                    // Badge on Pickup History: what is waiting for this user.
                    //   Dispatcher      -> New + On the Way (to take / to collect)
                    //   Receiving dept  -> Picked Up (on the way in)
                    //   admin/superadmin-> all three
                    $pickupBadgeStatuses = [];
                    $sidebarUser = auth()->user();
                    if ($sidebarUser) {
                        $isPickupAdmin = in_array($sidebarUser->role, ['admin', 'superadmin'], true);
                        if ($isPickupAdmin || $sidebarUser->department === \App\Models\Pickup::DESPATCH_DEPARTMENT) {
                            $pickupBadgeStatuses[] = \App\Models\Pickup::STATUS_NEW;
                            $pickupBadgeStatuses[] = \App\Models\Pickup::STATUS_ON_THE_WAY;
                        }
                        if ($isPickupAdmin || in_array($sidebarUser->department, \App\Models\Pickup::RECEIVING_DEPARTMENTS, true)) {
                            $pickupBadgeStatuses[] = \App\Models\Pickup::STATUS_PICKED_UP;
                        }
                    }
                    $pickupBadgeCount = $pickupBadgeStatuses
                        ? \App\Models\Pickup::whereIn('status', $pickupBadgeStatuses)->count()
                        : 0;
                @endphp
                <li class="menu-title"><span data-key="t-pickup">Pickup</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('pickups.create') }}">
                        <i class="las la-shipping-fast"></i> <span data-key="t-new-pickup">New Pickup</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('pickups.index') }}">
                        <i class="las la-clipboard-check"></i> <span data-key="t-pickup-history">Pickup History</span>
                        @if($pickupBadgeCount > 0)
                            <span class="badge badge-pill bg-danger ms-auto" title="Pickups waiting for you">{{ $pickupBadgeCount }}</span>
                        @endif
                    </a>
                </li>

                {{-- Settings section. The User link is open to everyone, because a
                     plain user still needs somewhere to edit their own account —
                     they just only ever see their own record there. Everything
                     below it stays admin and superadmin. --}}
                <li class="menu-title"><span data-key="t-setting">Setting</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('users.index') }}">
                        <i class="las la-user-cog"></i> <span data-key="t-user">
                            @if(Auth::user()->role == 'superadmin')
                                User
                            @else
                                My Account
                            @endif
                        </span>
                    </a>
                </li>
                {{-- Customer - open to every authenticated user. Staff need to
                     look up a customer and see what orders have gone to them. --}}
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('customers.index') }}">
                        <i class="las la-users"></i> <span data-key="t-customer">Customer</span>
                    </a>
                </li>
                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'superadmin')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('products.index') }}">
                        <i class="las la-box-open"></i> <span data-key="t-product">Product</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('blocked-dates.index') }}">
                        <i class="las la-calendar-times"></i> <span data-key="t-blocked-dates">Blocked Dates</span>
                    </a>
                </li>
                @if(Auth::user()->role == 'superadmin')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('logs.index') }}">
                        <i class="las la-clipboard-list"></i> <span data-key="t-logs">Activity Logs</span>
                    </a>
                </li>
                @endif
                @endif


            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>