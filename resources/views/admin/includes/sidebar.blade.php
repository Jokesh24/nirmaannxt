<style>
  .logo-text {
    color: #F28D3C;
    font-weight: bold;
  }

  /* =========================
     SUBMENU
  ========================== */
  .submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease;
    margin-left: 15px;
  }

  .submenu.open {
    max-height: 500px;
  }

  /* =========================
     DROPDOWN ARROW
  ========================== */
  .dropdown-toggle {
    position: relative;
  }

  .dropdown-toggle::after {
    content: "\F282";
    font-family: "bootstrap-icons";
    position: absolute;
    right: 15px;
    transition: transform 0.3s ease;
  }

  .dropdown-toggle.active::after {
    transform: rotate(180deg);
  }

  /* =========================
     ACTIVE MENU
  ========================== */
  .nav-link.active {
    background: rgba(242, 141, 60, 0.15);
    color: #F28D3C;
    font-weight: 600;
  }

  .nav-link.active i {
    color: #F28D3C;
  }

  .dropdown-toggle.active {
    color: #F28D3C;
  }

  .dropdown-toggle.active i {
    color: #F28D3C;
  }

  /* =========================
     LOGO
  ========================== */
  .sidebar-logo .logo-full {
    display: block;
    max-height: 100%;
  }

  .sidebar-logo .logo-mini {
    display: none;
    max-height: 36px;
    margin: 0 auto;
  }

  /* When sidebar is collapsed */
  .sidebar.collapsed .logo-full,
  body.sidebar-collapsed .logo-full {
    display: none;
  }

  .sidebar.collapsed .logo-mini,
  body.sidebar-collapsed .logo-mini {
    display: block;
  }

  .sidebar.collapsed .sidebar-logo {
    display: flex;
    justify-content: center;
    padding: 10px 0;
  }
</style>


<div class="overlay" id="overlay"></div>

<!-- Start Sidebar Section -->
<div class="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <a href="{{ route('admin.dashboard') }}">
      <img src="{{ asset('img/logo_admin.png') }}" class="logo-full" alt="Logo">
      <img src="{{ asset('img/logo_admin_mini.jpeg') }}" class="logo-mini" alt="Logo">
    </a>
  </div>


  <div class="sidebar-content">

    <!-- =========================
         DASHBOARD
    ========================== -->
    <a href="{{ route('admin.dashboard') }}"
      class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <i class="bi bi-speedometer2"></i>
      <span>Dashboard</span>
    </a>


    <!-- =========================
         MASTER DATA
    ========================== -->
    @php
      $masterDataActive =
        request()->routeIs('manage_size') ||
        request()->routeIs('manage_unit') ||
        request()->routeIs('manage_colour') ||
        request()->routeIs('manage_brand') ||
        request()->routeIs('manage_business_type');
    @endphp

    <a href="#" class="nav-link dropdown-toggle {{ $masterDataActive ? 'active' : '' }}">
      <i class="bi bi-database-gear"></i>
      <span>Master Data</span>
    </a>

    <div class="submenu {{ $masterDataActive ? 'open' : '' }}">

      <!-- Sizes -->
      <a href="{{ route('manage_size') }}" class="nav-link {{ request()->routeIs('manage_size') ? 'active' : '' }}">
        <i class="bi bi-arrows-angle-expand"></i>
        <span>Sizes</span>
      </a>

      <!-- Units -->
      <a href="{{ route('manage_unit') }}" class="nav-link {{ request()->routeIs('manage_unit') ? 'active' : '' }}">
        <i class="bi bi-rulers"></i>
        <span>Units</span>
      </a>

      <!-- Colours -->
      <a href="{{ route('manage_colour') }}" class="nav-link {{ request()->routeIs('manage_colour') ? 'active' : '' }}">
        <i class="bi bi-palette"></i>
        <span>Colours</span>
      </a>

      <!-- Brands -->
      <a href="{{ route('manage_brand') }}" class="nav-link {{ request()->routeIs('manage_brand') ? 'active' : '' }}">
        <i class="bi bi-tags"></i>
        <span>Brands</span>
      </a>

      <!-- Business Types  -->
      <a href="{{ route('manage_business_type') }}"
        class="nav-link {{ request()->routeIs('manage_business_type') ? 'active' : '' }}">
        <i class="bi bi-building"></i>
        <span>Business Types</span>
      </a>

      <!-- Banks -->
      <a href="{{ route('manage_bank') }}" class="nav-link {{ request()->routeIs('manage_bank') ? 'active' : '' }}">
        <i class="bi bi-bank"></i>
        <span>Banks</span>
      </a>

    </div>


    <!-- =========================
     APP SECTION
========================== -->
    @php
      $appSectionActive =
        request()->routeIs('admin.live_rates.*') ||
        request()->routeIs('admin.estimator.*') ||
        request()->routeIs('manage_coupon.*') ||
        request()->routeIs('admin.banners.*') ||
        request()->routeIs('admin.app_users.*');
    @endphp

    <a href="#" class="nav-link dropdown-toggle {{ $appSectionActive ? 'active' : '' }}">
      <i class="bi bi-phone"></i>
      <span>App Section</span>
    </a>

    <div class="submenu {{ $appSectionActive ? 'open' : '' }}">

      <!-- App Users -->
      <a href="{{ route('manage_app_users') }}"
        class="nav-link {{ request()->routeIs('manage_app_users.*') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span>App Users</span>
      </a>

      <!-- Live Rates -->
      <a href="{{ route('admin.live_rates.manage') }}"
        class="nav-link {{ request()->routeIs('admin.live_rates.*') ? 'active' : '' }}">
        <i class="bi bi-graph-up-arrow"></i>
        <span>Live Rates</span>
      </a>

      <!-- Estimator -->
      <a href="{{ route('admin.estimator.index') }}"
        class="nav-link {{ request()->routeIs('admin.estimator.*') ? 'active' : '' }}">
        <i class="bi bi-calculator"></i>
        <span>Estimator</span>
      </a>

      <!-- Discount Coupons -->
      <a href="{{ route('manage_coupon') }}"
        class="nav-link {{ request()->routeIs('manage_coupon.*') ? 'active' : '' }}">
        <i class="bi bi-ticket-perforated"></i>
        <span>Discount Coupons</span>
      </a>

      <!-- Banners -->
      <a href="{{ route('admin.banners.add') }}"
        class="nav-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
        <i class="bi bi-ticket-perforated"></i>
        <span>Banners</span>
      </a>

      <!-- Cancel Reasons -->
      <a href="{{ route('manage_cancel_reason') }}"
        class="nav-link {{ request()->routeIs('manage_cancel_reason') ? 'active' : '' }}">
        <i class="bi bi-x-circle"></i>
        <span>Cancel Reasons</span>
      </a>

    </div>

    <!-- =========================
         ORDERS SECTION
    ========================== -->
    @php
      $ordersActive =
        request()->routeIs('manage_orders') ||
        request()->routeIs('view_order') ||
        request()->routeIs('pending_qty_verifications') ||
        request()->routeIs('pending_customer_rejections');
    @endphp

    <a href="#" class="nav-link dropdown-toggle {{ $ordersActive ? 'active' : '' }}">
      <i class="bi bi-cart"></i>
      <span>Orders</span>
    </a>

    <div class="submenu {{ $ordersActive ? 'open' : '' }}">

      <!-- Manage Orders -->
      <a href="{{ route('manage_orders') }}"
        class="nav-link {{ request()->routeIs('manage_orders') || request()->routeIs('view_order') ? 'active' : '' }}">
        <i class="bi bi-list-check"></i>
        <span>All Orders</span>
      </a>

      <!-- Manage Orders -->
      <a href="{{ route('manage_bulk_quotes') }}"
        class="nav-link {{ request()->routeIs('manage_bulk_quotes') || request()->routeIs('view_order') ? 'active' : '' }}">
        <i class="bi bi-shield-x"></i>
        <span>Bulk Quotes</span>
      </a>

      <!-- Customer Rejections
      <a href="{{ route('manage_bulk_quotes') }}"
        class="nav-link {{ request()->routeIs('manage_bulk_quotes') ? 'active' : '' }}">
        <i class="bi bi-shield-x"></i>
        <span>Bulk Quotes</span>
      </a> -->

      <!-- Quantity Verifications -->
      <a href="{{ route('pending_qty_verifications') }}"
        class="nav-link {{ request()->routeIs('pending_qty_verifications') ? 'active' : '' }}">
        <i class="bi bi-ui-checks"></i>
        <span>Qty Verifications</span>
      </a>

      <!-- Customer Rejections -->
      <a href="{{ route('pending_customer_rejections') }}"
        class="nav-link {{ request()->routeIs('pending_customer_rejections') ? 'active' : '' }}">
        <i class="bi bi-shield-x"></i>
        <span>Customer Rejections</span>
      </a>

    </div>


    <!-- =========================
         PRODUCTS
    ========================== -->
    @php
      $productsActive =
        request()->routeIs('admin.product_categories.*') ||
        request()->routeIs('admin.product_subcategories.*') ||
        request()->routeIs('admin.products.*');
    @endphp

    <a href="#" class="nav-link dropdown-toggle {{ $productsActive ? 'active' : '' }}">
      <i class="bi bi-box-seam"></i>
      <span>Products</span>
    </a>

    <div class="submenu {{ $productsActive ? 'open' : '' }}">

      <!-- Categories -->
      <a href="{{ route('admin.product_categories.manage') }}"
        class="nav-link {{ request()->routeIs('admin.product_categories.*') ? 'active' : '' }}">
        <i class="bi bi-grid"></i>
        <span>Categories</span>
      </a>

      <!-- Sub Categories -->
      <a href="{{ route('admin.product_subcategories.manage') }}"
        class="nav-link {{ request()->routeIs('admin.product_subcategories.*') ? 'active' : '' }}">
        <i class="bi bi-diagram-3"></i>
        <span>Sub Categories</span>
      </a>

      <!-- Products -->
      <a href="{{ route('admin.products.manage') }}"
        class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
        <i class="bi bi-box2"></i>
        <span>Products</span>
      </a>

    </div>


    <!-- =========================
         VENDORS
    ========================== -->
    @php
      $vendorsActive =
        request()->routeIs('manage_vendor') ||
        request()->routeIs('admin.vendor_stock.*') ||
        request()->routeIs('admin.vendor_bulk.*');
    @endphp

    <a href="#" class="nav-link dropdown-toggle {{ $vendorsActive ? 'active' : '' }}">
      <i class="bi bi-shop-window"></i>
      <span>Vendors</span>
    </a>

    <div class="submenu {{ $vendorsActive ? 'open' : '' }}">

      <!-- Manage Vendors -->
      <a href="{{ route('manage_vendor') }}" class="nav-link {{ request()->routeIs('manage_vendor') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span>Vendors</span>
      </a>

      <!-- Vendor Stock -->
      <a href="{{ route('admin.vendor_stock.manage') }}"
        class="nav-link {{ request()->routeIs('admin.vendor_stock.*') ? 'active' : '' }}">
        <i class="bi bi-boxes"></i>
        <span>Vendor Stock</span>
      </a>

      <!-- Vendor Bulk Products -->
      <a href="{{ route('admin.vendor_bulk.manage') }}"
        class="nav-link {{ request()->routeIs('admin.vendor_bulk.*') ? 'active' : '' }}">
        <i class="bi bi-boxes"></i>
        <span>Vendor Bulk Products</span>
      </a>

    </div>

    <!-- =========================
            ENGINEERS
        ========================== -->
    @php
      $engineersActive =
        request()->routeIs('manage_engineer') ||
        request()->routeIs('add_engineer') ||
        request()->routeIs('edit_engineer') ||
        request()->routeIs('view_engineer');
    @endphp

    <a href="#" class="nav-link dropdown-toggle {{ $engineersActive ? 'active' : '' }}">
      <i class="bi bi-person-badge"></i>
      <span>Engineers</span>
    </a>

    <div class="submenu {{ $engineersActive ? 'open' : '' }}">

      <!-- Add Engineer -->
      <a href="{{ route('add_engineer') }}" class="nav-link {{ request()->routeIs('add_engineer') ? 'active' : '' }}">
        <i class="bi bi-person-plus"></i>
        <span>Add Engineer</span>
      </a>

      <!-- Manage Engineers -->
      <a href="{{ route('manage_engineer') }}"
        class="nav-link {{ request()->routeIs('manage_engineer') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span>Manage Engineers</span>
      </a>

    </div>

    <!-- =========================
            DELIVERY PARTNERS
        ========================== -->
    @php
      $deliveryPartnersActive =
        request()->routeIs('manage_delivery_partner') ||
        request()->routeIs('add_delivery_partner') ||
        request()->routeIs('edit_delivery_partner') ||
        request()->routeIs('view_delivery_partner');
    @endphp

    <a href="#" class="nav-link dropdown-toggle {{ $deliveryPartnersActive ? 'active' : '' }}">
      <i class="bi bi-truck"></i>
      <span>Delivery Partners</span>
    </a>

    <div class="submenu {{ $deliveryPartnersActive ? 'open' : '' }}">

      <!-- Add Delivery Partner -->
      <a href="{{ route('add_delivery_partner') }}"
        class="nav-link {{ request()->routeIs('add_delivery_partner') ? 'active' : '' }}">
        <i class="bi bi-person-plus"></i>
        <span>Add Partner</span>
      </a>

      <!-- Manage Delivery Partners -->
      <a href="{{ route('manage_delivery_partner') }}"
        class="nav-link {{ request()->routeIs('manage_delivery_partner') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span>Manage Partners</span>
      </a>

    </div>

    <!-- =========================
            APPLICATION CONTENT
        ========================== -->
    @php
      $contentActive =
        request()->is('admin/content/terms*') ||
        request()->is('admin/content/about*');

      $termsActive = request()->is('admin/content/terms*');
      $aboutActive = request()->is('admin/content/about*');
    @endphp

    <a href="#" class="nav-link dropdown-toggle {{ $contentActive ? 'active' : '' }}">
      <i class="bi bi-file-earmark-text"></i>
      <span>Application Content</span>
    </a>

    <div class="submenu {{ $contentActive ? 'open' : '' }}">

      <!-- Terms & Conditions -->
      <a href="{{ route('manage_content', 'terms') }}" class="nav-link {{ $termsActive ? 'active' : '' }}">
        <i class="bi bi-shield-check"></i>
        <span>Terms & Conditions</span>
      </a>

      <!-- About Nirmaan NXT -->
      <a href="{{ route('manage_content', 'about') }}" class="nav-link {{ $aboutActive ? 'active' : '' }}">
        <i class="bi bi-info-circle"></i>
        <span>About Nirmaan NXT</span>
      </a>

    </div>


    <!-- =========================
         CHANGE PASSWORD
    ========================== -->
    <a href="{{ route('admin.changePassword') }}"
      class="nav-link {{ request()->routeIs('admin.changePassword') ? 'active' : '' }}">
      <i class="bi bi-key"></i>
      <span>Change Password</span>
    </a>


    <!-- =========================
         LOGOUT
    ========================== -->
    <a href="{{ route('admin.logout') }}" class="nav-link">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </a>

  </div>
</div>
<!-- End Sidebar Section -->


<script>
  document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | Sidebar Dropdowns
    |--------------------------------------------------------------------------
    */

    const toggles = document.querySelectorAll(".dropdown-toggle");

    toggles.forEach(function (toggle) {

      toggle.addEventListener("click", function (e) {

        e.preventDefault();

        const submenu = this.nextElementSibling;

        if (!submenu || !submenu.classList.contains("submenu")) {
          return;
        }

        /*
         * Close other dropdowns
         */
        document.querySelectorAll(".submenu").forEach(function (menu) {

          if (menu !== submenu) {

            menu.classList.remove("open");
            menu.style.maxHeight = null;

            const parentToggle = menu.previousElementSibling;

            if (parentToggle) {
              /*
               * Don't remove active if child page
               * is currently active.
               */
              const activeChild = menu.querySelector(".nav-link.active");

              if (!activeChild) {
                parentToggle.classList.remove("active");
              }
            }
          }
        });


        /*
         * Toggle selected dropdown
         */
        if (submenu.classList.contains("open")) {

          submenu.classList.remove("open");
          submenu.style.maxHeight = null;

          /*
           * Keep parent highlighted when current
           * page belongs to this submenu.
           */
          if (!submenu.querySelector(".nav-link.active")) {
            this.classList.remove("active");
          }

        } else {

          submenu.classList.add("open");
          submenu.style.maxHeight = submenu.scrollHeight + "px";
          this.classList.add("active");
        }

      });

    });


    /*
    |--------------------------------------------------------------------------
    | Open Current Active Dropdown
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".submenu").forEach(function (submenu) {

      const activeChild = submenu.querySelector(".nav-link.active");

      if (activeChild) {

        submenu.classList.add("open");
        submenu.style.maxHeight = submenu.scrollHeight + "px";

        const parentToggle = submenu.previousElementSibling;

        if (parentToggle) {
          parentToggle.classList.add("active");
        }
      }

    });


    /*
    |--------------------------------------------------------------------------
    | Sidebar Collapse
    |--------------------------------------------------------------------------
    */

    const toggleBtn = document.getElementById("toggleBtn");
    const sidebar = document.querySelector(".sidebar");

    if (toggleBtn && sidebar) {

      toggleBtn.addEventListener("click", function () {

        sidebar.classList.toggle("collapsed");
        document.body.classList.toggle("sidebar-collapsed");

      });

    }

  });
</script>