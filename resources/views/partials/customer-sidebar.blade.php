@php
    $active = $active ?? '';
@endphp
<ul class="side-menu">
    <li class="sub-category"><h3>Main</h3></li>
    <li class="slide">
        <a class="side-menu__item" href="{{ url('/') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Shop</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Dashboard</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item {{ $active === 'orders' ? 'active' : '' }}" href="{{ route('orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">My Orders</span></a>
    </li>
    @if(auth()->user()->role?->name === 'service_center')
    <li class="slide">
        <a class="side-menu__item" href="{{ route('admin.pharmacy.referred-orders') }}"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Referral Orders</span></a>
    </li>
    @endif
    <li class="slide">
        <a class="side-menu__item {{ $active === 'invoices' ? 'active' : '' }}" href="{{ route('invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">My Invoices</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item {{ $active === 'blog' ? 'active' : '' }}" href="{{ route('my-blog.index') }}"><i class="side-menu__icon fe fe-edit"></i><span class="side-menu__label">My Blog</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item {{ $active === 'wallet' ? 'active' : '' }}" href="{{ route('wallet.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Wallet</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item {{ $active === 'dpbv' ? 'active' : '' }}" href="{{ route('dpbv.index') }}"><i class="side-menu__icon fe fe-award"></i><span class="side-menu__label">My DPBV</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item {{ $active === 'promo' ? 'active' : '' }}" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item {{ $active === 'bonus' ? 'active' : '' }}" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item {{ $active === 'contact' ? 'active' : '' }}" href="{{ route('contact.show') }}"><i class="side-menu__icon fe fe-mail"></i><span class="side-menu__label">Contact Us</span></a>
    </li>
    @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
    @php
        $dashSideRole = auth()->user()->role?->name;
        $dashSideAdminLabel = match($dashSideRole) {
            'reseller' => 'Reseller',
            'accountant' => 'Accountant Panel',
            'dispatch' => 'Dispatch Panel',
            'headquarters' => 'Admin Dashboard',
            'branch' => 'Branch Admin',
            'service_center' => 'Service Center Admin',
            default => 'Admin',
        };
    @endphp
    <li class="slide">
        <a class="side-menu__item" href="{{ $dashSideRole === 'headquarters' ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">{{ $dashSideAdminLabel }}</span></a>
    </li>
    @endif
    <li class="sub-category"><h3>Account</h3></li>
    <li class="slide">
        <a class="side-menu__item" href="{{ route('password.change') }}"><i class="side-menu__icon fe fe-lock"></i><span class="side-menu__label">Change Password</span></a>
    </li>
    <li class="slide">
        <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-user"></i><span class="side-menu__label">Profile</span></a>
    </li>
    <li class="slide">
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="side-menu__item border-0 bg-transparent w-100 text-start d-flex align-items-center"><i class="side-menu__icon fe fe-log-out"></i><span class="side-menu__label">Logout</span></button>
        </form>
    </li>
</ul>
